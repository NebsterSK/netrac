<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import type { TooltipItem } from 'chart.js';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Title,
    Tooltip as ChartTooltip,
} from 'chart.js';
import {
    ArrowUp,
    ChevronLeft,
    ChevronRight,
    EllipsisVertical,
    MessageSquare,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Bar } from 'vue-chartjs';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { Textarea } from '@/components/ui/textarea';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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
    amount: undefined as number | undefined,
    comment: '',
});

function openCreate() {
    editingBalance.value = null;
    form.date = `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}-01`;
    form.amount = undefined;
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

    return props.existingDates.some((date) => date.startsWith(key));
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

const averageBalance = computed(() => {
    const recent = recentBalances.value;

    if (recent.length === 0) {
        return 0;
    }

    const total = recent.reduce((sum, bal) => sum + bal.amount, 0);

    return Math.round(total / recent.length);
});

const groupedByYear = computed(() => {
    const groups: Record<number, Balance[]> = {};

    for (const balance of recentBalances.value) {
        const year = getYear(balance.date);
        (groups[year] ??= []).push(balance);
    }

    for (const year in groups) {
        groups[year].reverse();
    }

    return Object.entries(groups).sort(
        ([yearA], [yearB]) => Number(yearB) - Number(yearA),
    );
});

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    Title,
    ChartTooltip,
    Legend,
);

const sortedBalances = computed(() =>
    [...props.balances].sort(
        (balA, balB) =>
            new Date(balA.date).getTime() - new Date(balB.date).getTime(),
    ),
);

const recentBalances = computed(() => sortedBalances.value.slice(-18));

const chartData = computed(() => {
    const recent = recentBalances.value;
    const allSorted = sortedBalances.value;
    const labels = recent.map((bal) => {
        const date = new Date(bal.date);

        if (date.getMonth() === 0) {
            return date.toLocaleDateString('en-US', {
                month: 'short',
                year: 'numeric',
            });
        }

        return date.toLocaleDateString('en-US', { month: 'short' });
    });
    const amounts = recent.map((bal) => bal.amount);

    const rollingAverages = recent.map((bal) => {
        const entryDate = new Date(bal.date);
        const cutoff = new Date(entryDate);
        cutoff.setMonth(cutoff.getMonth() - 17);

        const window = allSorted.filter((other) => {
            const otherDate = new Date(other.date);

            return otherDate >= cutoff && otherDate <= entryDate;
        });

        const sum = window.reduce((total, entry) => total + entry.amount, 0);

        return Math.round(sum / window.length);
    });

    return {
        labels,
        datasets: [
            {
                type: 'line' as const,
                label: 'Average',
                data: rollingAverages,
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 5,
                tension: 0,
            },
            {
                label: 'Balance',
                data: amounts,
                backgroundColor: amounts.map((val) =>
                    val >= 0
                        ? 'rgba(34, 197, 94, 0.8)'
                        : 'rgba(239, 68, 68, 0.8)',
                ),
                borderColor: amounts.map((val) =>
                    val >= 0 ? 'rgb(34, 197, 94)' : 'rgb(239, 68, 68)',
                ),
                borderWidth: 1,
                borderRadius: 4,
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 16,
            },
        },
        tooltip: {
            callbacks: {
                title: (items: TooltipItem<'bar'>[]) => {
                    const bal = recentBalances.value[items[0].dataIndex];
                    const date = new Date(bal.date);

                    return date.toLocaleDateString('en-US', {
                        month: 'short',
                        year: 'numeric',
                    });
                },
                label: (context: TooltipItem<'bar'>) => {
                    const value = context.parsed.y ?? 0;
                    const sign = value >= 0 ? '+' : '−';
                    const formatted = `${sign}${Math.abs(value).toLocaleString('fr-FR')}`;

                    if (context.dataset.label === 'Average') {
                        return ` Avg: ${formatted}`;
                    }

                    return ` ${formatted}`;
                },
            },
        },
    },
    scales: {
        y: {
            grid: {
                color: 'rgba(128, 128, 128, 0.1)',
            },
            ticks: {
                callback: (value: number | string) => {
                    const num = Number(value);

                    return `${num >= 0 ? '+' : '−'}${Math.abs(num).toLocaleString('fr-FR')}`;
                },
            },
        },
        x: {
            grid: {
                display: false,
            },
        },
    },
};

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
        <div
            class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,4fr)]"
        >
            <Card>
                <CardHeader>
                    <CardTitle class="leading-8">Table</CardTitle>

                    <CardAction>
                        <Button
                            size="icon-sm"
                            variant="outline"
                            class="cursor-pointer"
                            @click="openCreate()"
                        >
                            <Plus class="size-3" />
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent>
                    <div
                        v-if="balances.length"
                        class="mb-3 flex items-center justify-between rounded-md bg-muted/50 px-4 py-2 text-lg"
                    >
                        <span>Average</span>

                        <span
                            class="font-mono font-medium"
                            :class="
                                averageBalance >= 0
                                    ? 'text-green-600 dark:text-green-400'
                                    : 'text-red-600 dark:text-red-400'
                            "
                        >
                            {{ averageBalance >= 0 ? '+' : '−'
                            }}{{ formatAmount(averageBalance) }}
                        </span>
                    </div>

                    <table
                        class="w-full text-left text-sm"
                        aria-label="Monthly balance entries"
                    >
                        <template
                            v-for="[year, balances] in groupedByYear"
                            :key="year"
                        >
                            <tbody>
                                <tr
                                    v-for="balance in balances"
                                    :key="balance.id"
                                    class="group/row border-b transition-colors last:border-0 hover:bg-muted/50"
                                >
                                    <td class="w-0 pl-4">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <button
                                                    class="flex h-full cursor-pointer items-center opacity-0 transition-opacity group-hover/row:opacity-100"
                                                    aria-label="Actions"
                                                >
                                                    <EllipsisVertical
                                                        class="size-4 text-muted-foreground"
                                                    />
                                                </button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="start">
                                                <DropdownMenuItem
                                                    class="cursor-pointer"
                                                    @click="openEdit(balance)"
                                                >
                                                    <Pencil class="size-4" />
                                                    Edit
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    class="cursor-pointer text-red-600 dark:text-red-400"
                                                    @click="
                                                        deleteBalance(balance)
                                                    "
                                                >
                                                    <Trash2 class="size-4" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span
                                            class="flex items-center gap-1 text-muted-foreground"
                                        >
                                            {{ formatMonth(balance.date) }}

                                            <TooltipProvider
                                                v-if="balance.comment"
                                            >
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <MessageSquare
                                                            class="size-3.5 cursor-pointer text-muted-foreground"
                                                        />
                                                    </TooltipTrigger>

                                                    <TooltipContent
                                                        side="right"
                                                    >
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
                                        {{ balance.amount >= 0 ? '+' : '−'
                                        }}{{ formatAmount(balance.amount) }}
                                    </td>
                                </tr>

                                <tr class="border-b bg-muted/50 last:border-0">
                                    <td
                                        class="px-4 py-3 font-semibold"
                                        colspan="3"
                                    >
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

                <CardContent class="min-h-0 flex-1">
                    <div v-if="balances.length >= 2" class="h-full">
                        <Bar :data="chartData as any" :options="chartOptions" />
                    </div>

                    <p v-else class="text-center text-sm text-muted-foreground">
                        Add at least 2 balance entries to see the chart.
                    </p>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="showDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        isEditing
                            ? 'Edit Monthly Balance'
                            : 'Add Monthly Balance'
                    }}</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <InputError :message="form.errors.date" />

                    <div class="space-y-2">
                        <Label>Date</Label>

                        <div
                            class="rounded-md border p-3"
                            :class="{
                                'pointer-events-none opacity-50':
                                    isEditing || form.processing,
                            }"
                        >
                            <div class="mb-3 flex items-center justify-between">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    class="cursor-pointer"
                                    @click="pickerYear--"
                                >
                                    <ChevronLeft class="size-4" />
                                </Button>

                                <span class="text-sm font-medium">{{
                                    pickerYear
                                }}</span>

                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    class="cursor-pointer"
                                    @click="pickerYear++"
                                >
                                    <ChevronRight class="size-4" />
                                </Button>
                            </div>

                            <div class="grid grid-cols-4 gap-1">
                                <Button
                                    v-for="(name, idx) in shortMonths"
                                    :key="idx"
                                    type="button"
                                    :variant="
                                        selectedYear === pickerYear &&
                                        selectedMonth === idx + 1
                                            ? 'default'
                                            : 'ghost'
                                    "
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
                        <Label for="balance-amount">Amount</Label>

                        <Input
                            id="balance-amount"
                            v-model="form.amount"
                            type="number"
                            class="h-14 text-2xl [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                            :disabled="form.processing"
                        />

                        <InputError :message="form.errors.amount" />
                    </div>

                    <div class="space-y-2">
                        <Label for="balance-comment"
                            >Comment
                            <span class="font-normal text-muted-foreground"
                                >(optional)</span
                            ></Label
                        >

                        <Textarea
                            id="balance-comment"
                            v-model="form.comment"
                            maxlength="255"
                            rows="4"
                            :disabled="form.processing"
                        />

                        <InputError :message="form.errors.comment" />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            class="cursor-pointer"
                            :disabled="form.processing"
                            @click="showDialog = false"
                        >
                            Cancel
                        </Button>

                        <Button
                            type="submit"
                            class="cursor-pointer"
                            :disabled="form.processing"
                        >
                            <Spinner v-if="form.processing" />
                            {{ isEditing ? 'Update' : 'Create' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
