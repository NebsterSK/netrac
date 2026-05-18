<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type MonthlyAverage = App.Data.Finance.MonthlyAverageData;

type PeriodAverages = App.Data.Finance.PeriodAveragesData;

const props = defineProps<{
    monthlyAverages: MonthlyAverage[];
    periodAverages: PeriodAverages;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

const monthNames = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
];

const averageByMonth = computed(() => {
    const map = new Map<number, MonthlyAverage>();

    for (const entry of props.monthlyAverages) {
        map.set(entry.month, entry);
    }

    return map;
});

const maxAbsAverage = computed(() => {
    if (props.monthlyAverages.length === 0) {
        return 1;
    }

    return Math.max(
        ...props.monthlyAverages.map((entry) => Math.abs(entry.average)),
        1,
    );
});

function heatmapColor(monthIndex: number): string {
    const entry = averageByMonth.value.get(monthIndex);

    if (!entry) {
        return 'bg-muted';
    }

    const intensity = Math.abs(entry.average) / maxAbsAverage.value;
    const level = Math.ceil(intensity * 4);

    if (entry.average >= 0) {
        const greens = [
            'bg-green-100 dark:bg-green-950',
            'bg-green-200 dark:bg-green-900',
            'bg-green-400 dark:bg-green-700',
            'bg-green-500 dark:bg-green-600',
        ];

        return greens[Math.min(level, 4) - 1] ?? greens[0];
    }

    const reds = [
        'bg-red-100 dark:bg-red-950',
        'bg-red-200 dark:bg-red-900',
        'bg-red-400 dark:bg-red-700',
        'bg-red-500 dark:bg-red-600',
    ];

    return reds[Math.min(level, 4) - 1] ?? reds[0];
}

function formatAmount(amount: number): string {
    const sign = amount >= 0 ? '+' : '−';

    return `${sign}${Math.abs(amount).toLocaleString('fr-FR')}`;
}

const periods = computed(() => [
    { key: 'last6', label: 'Last 6 months', value: props.periodAverages.last6 },
    {
        key: 'last12',
        label: 'Last 12 months',
        value: props.periodAverages.last12,
    },
    {
        key: 'last18',
        label: 'Last 18 months',
        value: props.periodAverages.last18,
    },
    { key: 'overall', label: 'Overall', value: props.periodAverages.overall },
]);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <Card class="aspect-video">
                    <CardHeader>
                        <CardTitle>Average Balance</CardTitle>
                    </CardHeader>

                    <CardContent class="flex-1">
                        <div class="grid h-full grid-cols-2 gap-3">
                            <div
                                v-for="period in periods"
                                :key="period.key"
                                class="flex flex-col items-center justify-center rounded-md bg-muted/50"
                            >
                                <span
                                    class="font-mono text-lg font-semibold"
                                    :class="
                                        period.value !== null &&
                                        period.value >= 0
                                            ? 'text-green-600 dark:text-green-400'
                                            : period.value !== null
                                              ? 'text-red-600 dark:text-red-400'
                                              : 'text-muted-foreground'
                                    "
                                >
                                    {{
                                        period.value !== null
                                            ? formatAmount(period.value)
                                            : '—'
                                    }}
                                </span>

                                <span
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ period.label }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="aspect-video">
                    <CardHeader>
                        <CardTitle>Monthly Balance Averages</CardTitle>
                    </CardHeader>

                    <CardContent class="flex-1">
                        <div class="grid h-full grid-cols-4 gap-2">
                            <TooltipProvider
                                v-for="(name, idx) in monthNames"
                                :key="idx"
                            >
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <div
                                            class="flex items-center justify-center rounded-md text-xs font-medium transition-colors"
                                            :class="[
                                                heatmapColor(idx + 1),
                                                averageByMonth.has(idx + 1)
                                                    ? 'text-white dark:text-white'
                                                    : 'text-muted-foreground',
                                            ]"
                                        >
                                            {{ name }}
                                        </div>
                                    </TooltipTrigger>

                                    <TooltipContent>
                                        <template
                                            v-if="averageByMonth.has(idx + 1)"
                                        >
                                            <p class="font-mono font-medium">
                                                {{
                                                    formatAmount(
                                                        averageByMonth.get(
                                                            idx + 1,
                                                        )!.average,
                                                    )
                                                }}
                                            </p>

                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{
                                                    averageByMonth.get(idx + 1)!
                                                        .count
                                                }}
                                                {{
                                                    averageByMonth.get(idx + 1)!
                                                        .count === 1
                                                        ? 'entry'
                                                        : 'entries'
                                                }}
                                            </p>
                                        </template>

                                        <template v-else>
                                            <p class="text-muted-foreground">
                                                No data
                                            </p>
                                        </template>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </CardContent>
                </Card>

                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
            </div>

            <div
                class="relative min-h-screen flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>
