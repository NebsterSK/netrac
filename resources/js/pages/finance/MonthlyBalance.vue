<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import type {
    Chart,
    LegendItem,
    ScriptableLineSegmentContext,
    TooltipItem,
} from 'chart.js';
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
import { ArrowUp, MessageSquare } from 'lucide-vue-next';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAppearance } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { index } from '@/routes/finance/monthly-balance';
import type { BreadcrumbItem } from '@/types';

type Balance = App.Data.Finance.MonthlyBalanceData;

const props = defineProps<{
    balances: Balance[];
}>();

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
    const recent = recentBalances.value.filter((bal) => !bal.is_draft);

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

// A draft month is a prediction, so it never feeds the averages.
const realBalances = computed(() =>
    sortedBalances.value.filter((bal) => !bal.is_draft),
);

/** Phones get thinner lines and smaller points so dense series stay readable. */
const isPhone = useMediaQuery('(max-width: 639px)');

/**
 * Diagonal stripes for a draft bar, echoing the striped draft rows in the table.
 */
function stripePattern(color: string): CanvasPattern | string {
    const tile = document.createElement('canvas');
    tile.width = 8;
    tile.height = 8;

    const context = tile.getContext('2d');

    if (!context) {
        return color;
    }

    context.strokeStyle = color;
    context.lineWidth = 2;
    context.beginPath();
    context.moveTo(0, 8);
    context.lineTo(8, 0);
    context.moveTo(-2, 2);
    context.lineTo(2, -2);
    context.moveTo(6, 10);
    context.lineTo(10, 6);
    context.stroke();

    return context.createPattern(tile, 'repeat') ?? color;
}

const chartData = computed(() => {
    const recent = recentBalances.value;
    const allSorted = realBalances.value;
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
                borderWidth: isPhone.value ? 2 : 3,
                pointRadius: isPhone.value ? 2 : 5,
                pointBackgroundColor: recent.map((bal) =>
                    bal.is_draft ? 'transparent' : 'rgb(99, 102, 241)',
                ),
                pointHoverRadius: isPhone.value ? 3 : 5,
                tension: 0,
                segment: {
                    borderDash: (ctx: ScriptableLineSegmentContext) =>
                        recent[ctx.p1DataIndex]?.is_draft ? [6, 6] : undefined,
                },
            },
            {
                label: 'Balance',
                data: amounts,
                backgroundColor: recent.map((bal) => {
                    if (bal.is_draft) {
                        return stripePattern(
                            bal.amount >= 0
                                ? 'rgba(34, 197, 94, 0.6)'
                                : 'rgba(239, 68, 68, 0.6)',
                        );
                    }

                    return bal.amount >= 0
                        ? 'rgba(34, 197, 94, 0.8)'
                        : 'rgba(239, 68, 68, 0.8)';
                }),
                borderColor: amounts.map((val) =>
                    val >= 0 ? 'rgb(34, 197, 94)' : 'rgb(239, 68, 68)',
                ),
                borderWidth: recent.map((bal) => (bal.is_draft ? 2 : 1)),
                borderRadius: 4,
            },
        ],
    };
});

const { resolvedAppearance } = useAppearance();

const chartOptions = computed(() => {
    const textColor =
        resolvedAppearance.value === 'dark'
            ? 'rgb(229, 229, 229)'
            : 'rgb(38, 38, 38)';

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                labels: {
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 16,
                    color: textColor,
                    generateLabels: (chart: Chart): LegendItem[] =>
                        chart.data.datasets.map((dataset, index) => ({
                            text: dataset.label ?? '',
                            fillStyle:
                                dataset.label === 'Balance'
                                    ? 'rgba(34, 197, 94, 0.8)'
                                    : (dataset.backgroundColor as string),
                            strokeStyle:
                                dataset.label === 'Balance'
                                    ? 'rgb(34, 197, 94)'
                                    : (dataset.borderColor as string),
                            lineWidth: 1,
                            hidden: !chart.isDatasetVisible(index),
                            datasetIndex: index,
                            fontColor: textColor,
                        })),
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
                    color: textColor,
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
                ticks: {
                    color: textColor,
                },
            },
        },
    };
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
        <div
            class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,4fr)]"
        >
            <Card class="self-start">
                <CardHeader>
                    <CardTitle class="leading-8">Table</CardTitle>
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

                    <p
                        v-else
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        Add at least 2 net worth statements to see monthly
                        balances.
                    </p>

                    <table
                        class="w-full text-left text-sm"
                        aria-label="Monthly balances"
                    >
                        <template
                            v-for="[year, balances] in groupedByYear"
                            :key="year"
                        >
                            <tbody>
                                <tr
                                    v-for="balance in balances"
                                    :key="balance.date"
                                    class="border-b transition-colors last:border-0 hover:bg-muted/50"
                                    :class="{
                                        'bg-[repeating-linear-gradient(-45deg,transparent_0_6px,var(--color-muted)_6px_12px)] italic opacity-70':
                                            balance.is_draft,
                                    }"
                                >
                                    <td class="px-4 py-3">
                                        <span
                                            class="flex items-center gap-1 text-muted-foreground"
                                        >
                                            {{ formatMonth(balance.date) }}

                                            <TooltipProvider
                                                v-if="balance.note"
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
                                                        {{ balance.note }}
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>

                                            <Badge
                                                v-if="balance.is_draft"
                                                variant="outline"
                                                class="ml-1 border-dashed not-italic"
                                            >
                                                Draft
                                            </Badge>
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
                                        colspan="2"
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

            <Card class="sticky top-0 h-[90vh] self-start">
                <CardHeader>
                    <CardTitle>Chart</CardTitle>
                </CardHeader>

                <CardContent class="min-h-0 flex-1 overflow-hidden">
                    <div v-if="balances.length >= 2" class="h-full">
                        <Bar :data="chartData as any" :options="chartOptions" />
                    </div>

                    <p v-else class="text-center text-sm text-muted-foreground">
                        Add at least 3 net worth statements to see the chart.
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
