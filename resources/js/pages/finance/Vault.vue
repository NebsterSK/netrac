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
} from '@/routes/finance/vaults';
import type { BreadcrumbItem } from '@/types';

type Vault = App.Data.Finance.VaultData;

const props = defineProps<{
    vaults: Vault[];
}>();

const showDialog = ref(false);
const editingVault = ref<Vault | null>(null);
const isEditing = computed(() => editingVault.value !== null);
const reordering = ref(false);

const form = useForm({
    name: '',
    color: null as string | null,
    tracks_monthly_balance: false,
});

function openCreate() {
    editingVault.value = null;
    form.reset();
    form.clearErrors();
    showDialog.value = true;
}

function openEdit(vault: Vault) {
    editingVault.value = vault;
    form.name = vault.name;
    form.color = vault.color;
    form.tracks_monthly_balance = vault.tracks_monthly_balance;
    form.clearErrors();
    showDialog.value = true;
}

function submitForm() {
    const url = isEditing.value
        ? update.url(editingVault.value!.id)
        : store.url();

    const method = isEditing.value ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            showDialog.value = false;
            editingVault.value = null;
            form.reset();
        },
    });
}

/** Swap the vault with its neighbour; the order drives Net Worth columns and chart stacking. */
function move(position: number, offset: -1 | 1) {
    const ids = props.vaults.map((vault) => vault.id);
    const target = position + offset;

    [ids[position], ids[target]] = [ids[target], ids[position]];

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

function deleteVault(vault: Vault) {
    const note =
        vault.statements_count && vault.statements_count > 0
            ? ` This also removes its amounts from ${vault.statements_count} statement${vault.statements_count === 1 ? '' : 's'}, lowering their net worth totals.`
            : '';

    if (!confirm(`Are you sure you want to delete this vault?${note}`)) {
        return;
    }

    router.delete(destroy.url(vault.id), { preserveScroll: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Vaults',
        href: index(),
    },
];
</script>

<template>
    <Head title="Vaults" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-1 gap-4 p-4 lg:max-w-2xl">
            <Card class="self-start">
                <CardHeader>
                    <CardTitle class="leading-8">Vaults</CardTitle>

                    <CardAction>
                        <Button
                            size="icon-sm"
                            variant="outline"
                            class="cursor-pointer"
                            aria-label="Add vault"
                            @click="openCreate()"
                        >
                            <Plus class="size-3" />
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent>
                    <table
                        class="w-full text-left text-sm"
                        aria-label="Vaults"
                    >
                        <thead class="border-b text-muted-foreground">
                            <tr>
                                <th class="w-0 px-2 py-2"></th>

                                <th class="px-4 py-2 font-medium">Name</th>

                                <th class="px-4 py-2 font-medium">
                                    Statements
                                </th>

                                <th class="w-0 px-4 py-2"></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-if="vaults.length === 0">
                                <td
                                    colspan="4"
                                    class="px-4 py-6 text-center text-muted-foreground"
                                >
                                    No vaults yet.
                                </td>
                            </tr>

                            <tr
                                v-for="(vault, position) in vaults"
                                :key="vault.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/50"
                            >
                                <td class="px-2 py-3">
                                    <div class="flex items-center">
                                        <Button
                                            size="icon-sm"
                                            variant="ghost"
                                            class="cursor-pointer"
                                            aria-label="Move up"
                                            :disabled="
                                                position === 0 || reordering
                                            "
                                            @click="move(position, -1)"
                                        >
                                            <ArrowUp class="size-4" />
                                        </Button>

                                        <Button
                                            size="icon-sm"
                                            variant="ghost"
                                            class="cursor-pointer"
                                            aria-label="Move down"
                                            :disabled="
                                                position ===
                                                    vaults.length - 1 ||
                                                reordering
                                            "
                                            @click="move(position, 1)"
                                        >
                                            <ArrowDown class="size-4" />
                                        </Button>
                                    </div>
                                </td>

                                <td class="px-4 py-3 font-medium">
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="size-2.5 shrink-0 rounded-full"
                                            :class="{
                                                'border border-dashed border-muted-foreground':
                                                    !vault.color,
                                            }"
                                            :style="{
                                                backgroundColor:
                                                    vault.color ?? undefined,
                                            }"
                                        />

                                        {{ vault.name }}

                                        <Badge
                                            v-if="vault.tracks_monthly_balance"
                                            variant="outline"
                                            class="text-xs font-normal"
                                        >
                                            Monthly balance
                                        </Badge>
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <Badge variant="secondary" class="text-xs">
                                        {{ vault.statements_count ?? 0 }}
                                    </Badge>
                                </td>

                                <td class="px-4 py-3 text-right">
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
                                                @click="openEdit(vault)"
                                            >
                                                <Pencil class="size-4" />
                                                Edit
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                class="cursor-pointer text-red-600 dark:text-red-400"
                                                @click="deleteVault(vault)"
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
                        isEditing ? 'Edit Vault' : 'Add Vault'
                    }}</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <fieldset :disabled="form.processing" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="vault-name">Name</Label>

                            <Input
                                id="vault-name"
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
                                for="vault-tracks-monthly-balance"
                                class="flex items-center gap-3"
                            >
                                <Checkbox
                                    id="vault-tracks-monthly-balance"
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
                                The monthly balance is this vault's change since
                                the previous statement. Only one vault can drive
                                it.
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
