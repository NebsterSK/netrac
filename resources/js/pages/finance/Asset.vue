<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    EllipsisVertical,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ColorPicker from '@/components/ColorPicker.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import {
    destroy,
    index,
    reorder,
    store,
    update,
} from '@/routes/finance/assets';
import type { BreadcrumbItem } from '@/types';

type Asset = App.Data.Finance.AssetData;

const props = defineProps<{
    assets: Asset[];
}>();

const showDialog = ref(false);
const editingAsset = ref<Asset | null>(null);
const isEditing = computed(() => editingAsset.value !== null);
const reordering = ref(false);

const form = useForm({
    name: '',
    color: null as string | null,
    tracks_monthly_balance: false,
});

function openCreate() {
    editingAsset.value = null;
    form.reset();
    form.clearErrors();
    showDialog.value = true;
}

function openEdit(asset: Asset) {
    editingAsset.value = asset;
    form.name = asset.name;
    form.color = asset.color;
    form.tracks_monthly_balance = asset.tracks_monthly_balance;
    form.clearErrors();
    showDialog.value = true;
}

function submitForm() {
    const url = isEditing.value
        ? update.url(editingAsset.value!.id)
        : store.url();

    const method = isEditing.value ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            showDialog.value = false;
            editingAsset.value = null;
            form.reset();
        },
    });
}

/** Swap an asset with its neighbour; the order is the Net Worth column order. */
function move(asset: Asset, offset: -1 | 1) {
    const ids = props.assets.map((item) => item.id);
    const from = ids.indexOf(asset.id);
    const to = from + offset;

    if (to < 0 || to >= ids.length) {
        return;
    }

    [ids[from], ids[to]] = [ids[to], ids[from]];

    router.put(
        reorder.url(),
        { ids },
        {
            preserveScroll: true,
            onStart: () => (reordering.value = true),
            onFinish: () => (reordering.value = false),
        },
    );
}

function deleteAsset(asset: Asset) {
    const note =
        asset.balances_count && asset.balances_count > 0
            ? ` This also removes its balance from ${asset.balances_count} statement${asset.balances_count === 1 ? '' : 's'}.`
            : '';

    if (!confirm(`Are you sure you want to delete this asset?${note}`)) {
        return;
    }

    router.delete(destroy.url(asset.id), { preserveScroll: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Assets',
        href: index(),
    },
];
</script>

<template>
    <Head title="Assets" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-1 gap-4 p-4 lg:max-w-2xl">
            <Card class="self-start">
                <CardHeader>
                    <CardTitle class="leading-8">Assets</CardTitle>

                    <CardAction>
                        <Button
                            size="icon-sm"
                            variant="outline"
                            class="cursor-pointer"
                            aria-label="Add asset"
                            @click="openCreate()"
                        >
                            <Plus class="size-3" />
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent>
                    <table
                        class="w-full text-left text-sm"
                        aria-label="Assets"
                    >
                        <thead class="border-b text-muted-foreground">
                            <tr>
                                <th class="px-4 py-2 font-medium">Name</th>

                                <th class="px-4 py-2 font-medium">
                                    Statements
                                </th>

                                <th class="w-0 px-4 py-2"></th>
                            </tr>
                        </thead>

                        <tbody
                            class="transition-opacity"
                            :class="{ 'opacity-50': reordering }"
                        >
                            <tr v-if="assets.length === 0">
                                <td
                                    colspan="3"
                                    class="px-4 py-6 text-center text-muted-foreground"
                                >
                                    No assets yet.
                                </td>
                            </tr>

                            <tr
                                v-for="(asset, position) in assets"
                                :key="asset.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/50"
                            >
                                <td class="px-4 py-3 font-medium">
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="size-2.5 shrink-0 rounded-full"
                                            :class="{
                                                'border border-dashed border-muted-foreground':
                                                    !asset.color,
                                            }"
                                            :style="{
                                                backgroundColor:
                                                    asset.color ?? undefined,
                                            }"
                                        />

                                        {{ asset.name }}

                                        <Badge
                                            v-if="asset.tracks_monthly_balance"
                                            variant="outline"
                                            class="text-xs font-normal"
                                        >
                                            Monthly balance
                                        </Badge>
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <Badge variant="secondary" class="text-xs">
                                        {{ asset.balances_count ?? 0 }}
                                    </Badge>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <button
                                                class="flex cursor-pointer items-center disabled:cursor-default disabled:opacity-50"
                                                aria-label="Actions"
                                                :disabled="reordering"
                                            >
                                                <EllipsisVertical
                                                    class="size-4 text-muted-foreground"
                                                />
                                            </button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem
                                                class="cursor-pointer"
                                                @click="openEdit(asset)"
                                            >
                                                <Pencil class="size-4" />
                                                Edit
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="position > 0"
                                                class="cursor-pointer"
                                                @click="move(asset, -1)"
                                            >
                                                <ArrowUp class="size-4" />
                                                Move up
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="
                                                    position < assets.length - 1
                                                "
                                                class="cursor-pointer"
                                                @click="move(asset, 1)"
                                            >
                                                <ArrowDown class="size-4" />
                                                Move down
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                class="cursor-pointer text-red-600 dark:text-red-400"
                                                @click="deleteAsset(asset)"
                                            >
                                                <Trash2 class="size-4" />
                                                Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="showDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        isEditing ? 'Edit Asset' : 'Add Asset'
                    }}</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <fieldset :disabled="form.processing" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="asset-name">Name</Label>

                            <Input
                                id="asset-name"
                                v-model="form.name"
                                type="text"
                                maxlength="255"
                                autofocus
                            />

                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label>Color</Label>

                            <ColorPicker v-model="form.color" />

                            <InputError :message="form.errors.color" />
                        </div>

                        <div class="space-y-2">
                            <Label
                                for="asset-tracks-monthly-balance"
                                class="flex items-center gap-3"
                            >
                                <Checkbox
                                    id="asset-tracks-monthly-balance"
                                    :model-value="form.tracks_monthly_balance"
                                    @update:model-value="
                                        (checked) =>
                                            (form.tracks_monthly_balance =
                                                checked === true)
                                    "
                                />

                                <span>Drives the monthly balance</span>
                            </Label>

                            <p class="text-xs text-muted-foreground">
                                Monthly balances are this asset's change between
                                statements. Only one asset can drive them.
                            </p>

                            <InputError
                                :message="form.errors.tracks_monthly_balance"
                            />
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                class="cursor-pointer"
                                @click="showDialog = false"
                            >
                                Cancel
                            </Button>

                            <Button type="submit" class="cursor-pointer">
                                <Spinner v-if="form.processing" />
                                {{ isEditing ? 'Update' : 'Create' }}
                            </Button>
                        </DialogFooter>
                    </fieldset>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
