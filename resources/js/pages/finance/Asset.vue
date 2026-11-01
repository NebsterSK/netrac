<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { EllipsisVertical, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ColorPicker from '@/components/ColorPicker.vue';
import InputError from '@/components/InputError.vue';
import OwnerBadge from '@/components/OwnerBadge.vue';
import OwnerSelect from '@/components/OwnerSelect.vue';
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
import { useOwners } from '@/composables/useOwners';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { destroy, index, store, update } from '@/routes/finance/assets';
import type { BreadcrumbItem } from '@/types';

type Asset = App.Data.Finance.AssetData;

defineProps<{
    assets: Asset[];
}>();

const showDialog = ref(false);
const editingAsset = ref<Asset | null>(null);
const isEditing = computed(() => editingAsset.value !== null);
const ownerLocked = computed(
    () => (editingAsset.value?.statements_count ?? 0) > 0,
);

const { ownerById, defaultOwnerId } = useOwners();

const form = useForm({
    user_id: null as number | null,
    name: '',
    color: null as string | null,
    tracks_monthly_balance: false,
});

function openCreate() {
    editingAsset.value = null;
    form.reset();
    form.user_id = defaultOwnerId.value;
    form.clearErrors();
    showDialog.value = true;
}

function openEdit(asset: Asset) {
    editingAsset.value = asset;
    form.user_id = asset.user_id;
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

function deleteAsset(asset: Asset) {
    const note =
        asset.statements_count && asset.statements_count > 0
            ? ` This also removes its amounts from ${asset.statements_count} statement${asset.statements_count === 1 ? '' : 's'}, lowering their net worth totals.`
            : '';
    const trackingNote = asset.tracks_monthly_balance
        ? ' It drives the monthly balance, so Monthly Balance and the dashboard stay empty until you mark another asset.'
        : '';

    if (
        !confirm(
            `Are you sure you want to delete this asset?${note}${trackingNote}`,
        )
    ) {
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
                    <table class="w-full text-left text-sm" aria-label="Assets">
                        <thead class="border-b text-muted-foreground">
                            <tr>
                                <th class="px-2 py-2 font-medium sm:px-4">
                                    Name
                                </th>

                                <th
                                    class="hidden px-4 py-2 font-medium sm:table-cell"
                                >
                                    Statements
                                </th>

                                <th class="w-0 px-2 py-2 sm:px-4"></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-if="assets.length === 0">
                                <td
                                    colspan="3"
                                    class="px-4 py-6 text-center text-muted-foreground"
                                >
                                    No assets yet.
                                </td>
                            </tr>

                            <tr
                                v-for="asset in assets"
                                :key="asset.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/50"
                            >
                                <td class="px-2 py-3 font-medium sm:px-4">
                                    <span
                                        class="flex flex-wrap items-center gap-x-2 gap-y-1"
                                    >
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

                                        <OwnerBadge
                                            v-if="ownerById(asset.user_id)"
                                            :owner="ownerById(asset.user_id)!"
                                        />

                                        <Badge
                                            v-if="asset.tracks_monthly_balance"
                                            variant="outline"
                                            class="text-xs font-normal"
                                        >
                                            Monthly balance
                                        </Badge>
                                    </span>
                                </td>

                                <td class="hidden px-4 py-3 sm:table-cell">
                                    <Badge variant="secondary" class="text-xs">
                                        {{ asset.statements_count ?? 0 }}
                                    </Badge>
                                </td>

                                <td class="px-2 py-3 text-right sm:px-4">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <button
                                                class="flex cursor-pointer items-center"
                                                aria-label="Actions"
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
                            <Label for="asset-owner">Owner</Label>

                            <OwnerSelect
                                id="asset-owner"
                                v-model="form.user_id"
                                :disabled="ownerLocked"
                            />

                            <p
                                v-if="ownerLocked"
                                class="text-xs text-muted-foreground"
                            >
                                The owner can't change because this asset is
                                used in statements.
                            </p>

                            <InputError :message="form.errors.user_id" />
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
                                The monthly balance is this asset's change since
                                the previous statement. Only one asset per owner
                                can drive it.
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
