<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowUp, ChevronLeft, ChevronRight, EllipsisVertical, MessageSquare, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { destroy, index, store, update } from '@/routes/monthly-balance';
import type { BreadcrumbItem } from '@/types';

interface Balance {
    id: number;
    date: string;
    amount: number;
    comment: string | null;
}

const props = defineProps<{
    balances: Balance[];
    existingDates: string[];
}>();

const showDialog = ref(false);
const editingBalance = ref<Balance | null>(null);
const isEditing = computed(() => editingBalance.value !== null);
const pickerYear = ref(new Date().getFullYear());

const shortMonths = Array.from({ length: 12 }, (_, idx) =>
    new Date(2000, idx).toLocaleDateString('en-US', { month: 'short' }),
);

const form = useForm({
    date: `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}-01`,
    amount: 0,
    comment: '',
});

function openCreate() {
    editingBalance.value = null;
    form.date = `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}-01`;
    form.amount = 0;
    form.comment = '';
    pickerYear.value = new Date().getFullYear();
    showDialog.value = true;
}

function openEdit(balance: Balance) {
    editingBalance.value = balance;
    const date = new Date(balance.date);
    form.date = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-01`;
    form.amount = balance.amount;
    form.comment = balance.comment ?? '';
    pickerYear.value = date.getFullYear();
    showDialog.value = true;
}

const selectedYear = computed(() => parseInt(form.date.split('-')[0]));
const selectedMonth = computed(() => parseInt(form.date.split('-')[1]));

function isMonthDisabled(month: number): boolean {
    const key = `${pickerYear.value}-${String(month).padStart(2, '0')}`;

    return props.existingDates.includes(key);
}

function selectMonth(month: number) {
    form.date = `${pickerYear.value}-${String(month).padStart(2, '0')}-01`;
}

function submitForm() {
    const url = isEditing.value
        ? update.url(editingBalance.value!.id)
        : store.url();

    const method = isEditing.value ? 'put' : 'post';

    form.transform((data) => ({
        date: data.date,
        amount: data.amount,
        comment: data.comment || null,
    }))[method](url, {
        onSuccess: () => {
            showDialog.value = false;
            editingBalance.value = null;
            form.reset();
        },
    });
}

function deleteBalance(balance: Balance) {
    if (!confirm('Are you sure you want to delete this balance entry?')) {
        return;
    }

    router.delete(destroy.url(balance.id));
}

function formatMonth(date: string): string {
    return new Date(date).toLocaleDateString('en-US', { month: 'long' });
}

function formatAmount(amount: number): string {
    return Math.abs(amount).toLocaleString('fr-FR');
}

function getYear(date: string): number {
    return new Date(date).getFullYear();
}

const groupedByYear = computed(() => {
    const groups: Record<number, Balance[]> = {};

    for (const balance of props.balances) {
        const year = getYear(balance.date);
        (groups[year] ??= []).push(balance);
    }

    return Object.entries(groups)
        .sort(([yearA], [yearB]) => Number(yearB) - Number(yearA));
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Monthly Balance',
        href: index(),
    },
];
</script>

<template>
    <Head title="Monthly Balance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-[20%_80%] gap-4 p-4">
            <Card>
                <CardHeader>
                    <CardTitle>Table</CardTitle>

                    <CardAction>
                        <Button size="icon-sm" variant="outline" class="cursor-pointer" @click="openCreate()">
                            <Plus class="size-4" />
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent>
                    <table class="w-full text-left text-sm">
                        <template v-for="[year, balances] in groupedByYear" :key="year">
                            <tbody>
                                <tr
                                    v-for="balance in balances"
                                    :key="balance.id"
                                    class="group/row border-b last:border-0 transition-colors hover:bg-muted/50"
                                >
                                    <td class="w-0 pl-4">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <button class="flex h-full cursor-pointer items-center opacity-0 transition-opacity group-hover/row:opacity-100">
                                                    <EllipsisVertical class="size-4 text-muted-foreground" />
                                                </button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="start">
                                                <DropdownMenuItem class="cursor-pointer" @click="openEdit(balance)">
                                                    <Pencil class="size-4" />
                                                    Edit
                                                </DropdownMenuItem>

                                                <DropdownMenuItem class="cursor-pointer text-red-600 dark:text-red-400" @click="deleteBalance(balance)">
                                                    <Trash2 class="size-4" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="flex items-center gap-1.5">
                                            {{ formatMonth(balance.date) }}

                                            <TooltipProvider v-if="balance.comment">
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <MessageSquare class="size-3.5 cursor-pointer text-muted-foreground" />
                                                    </TooltipTrigger>

                                                    <TooltipContent side="right">
                                                        {{ balance.comment }}
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        </span>
                                    </td>

                                    <td
                                        class="px-4 py-3 text-right font-mono"
                                        :class="
                                            balance.amount >= 0
                                                ? 'text-green-600 dark:text-green-400'
                                                : 'text-red-600 dark:text-red-400'
                                        "
                                    >
                                        {{ balance.amount >= 0 ? '+' : '−' }}{{ formatAmount(balance.amount) }}
                                    </td>
                                </tr>

                                <tr class="border-b bg-muted/50 last:border-0">
                                    <td class="px-4 py-3 font-semibold" colspan="3">
                                        <span class="flex items-center gap-1">
                                            <ArrowUp class="size-4" />

                                            {{ year }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </template>
                    </table>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Chart</CardTitle>
                </CardHeader>

                <CardContent> </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="showDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Edit Monthly Balance' : 'Add Monthly Balance' }}</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="space-y-2">
                        <Label>Date</Label>

                        <div class="rounded-md border p-3" :class="{ 'pointer-events-none opacity-50': isEditing }">
                            <div class="mb-3 flex items-center justify-between">
                                <Button type="button" variant="ghost" size="icon-sm" class="cursor-pointer" @click="pickerYear--">
                                    <ChevronLeft class="size-4" />
                                </Button>

                                <span class="text-sm font-medium">{{ pickerYear }}</span>

                                <Button type="button" variant="ghost" size="icon-sm" class="cursor-pointer" @click="pickerYear++">
                                    <ChevronRight class="size-4" />
                                </Button>
                            </div>

                            <div class="grid grid-cols-4 gap-1">
                                <Button
                                    v-for="(name, idx) in shortMonths"
                                    :key="idx"
                                    type="button"
                                    :variant="selectedYear === pickerYear && selectedMonth === idx + 1 ? 'default' : 'ghost'"
                                    size="sm"
                                    class="cursor-pointer"
                                    :disabled="isMonthDisabled(idx + 1)"
                                    @click="selectMonth(idx + 1)"
                                >
                                    {{ name }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Amount</Label>

                        <Input v-model="form.amount" type="number" class="h-14 text-2xl" />
                    </div>

                    <div class="space-y-2">
                        <Label>Comment <span class="text-muted-foreground font-normal">(optional)</span></Label>

                        <Textarea v-model="form.comment" maxlength="255" rows="4" />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" class="cursor-pointer" @click="showDialog = false">
                            Cancel
                        </Button>

                        <Button type="submit" class="cursor-pointer" :disabled="form.processing">
                            {{ isEditing ? 'Update' : 'Create' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
