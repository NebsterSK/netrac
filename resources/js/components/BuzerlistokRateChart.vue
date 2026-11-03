<script setup lang="ts">
import type { ScriptableContext, TooltipItem } from 'chart.js';
import {
    CategoryScale,
    Chart as ChartJS,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip as ChartTooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import { useAppearance } from '@/composables/useAppearance';
import {
    successRateColor,
    todayString,
    weekSuccessRate,
} from '@/lib/buzerlistok';

type Week = App.Data.Buzerlistok.WeekData;

const props = defineProps<{
    weeks: Week[];
}>();

ChartJS.register(
    CategoryScale,
    LinearScale,
    LineElement,
    PointElement,
    ChartTooltip,
);

/** Weeks that already have a rate, oldest first. */
const ratedWeeks = computed(() => {
    const today = todayString();

    return props.weeks
        .map((week) => ({ week, rate: weekSuccessRate(week, today) }))
        .filter(
            (entry): entry is { week: Week; rate: number } =>
                entry.rate !== null,
        );
});

/** `starts_on` as a European day.month label, e.g. "27. 10.". */
function weekLabel(week: Week): string {
    const [, month, day] = week.starts_on.split('-').map(Number);

    return `${day}. ${month}.`;
}

/** Paints the line from red at 0 % to green at 100 % along the y axis. */
function lineGradient(context: ScriptableContext<'line'>) {
    const { chart } = context;
    const { chartArea, scales } = chart;

    if (!chartArea) {
        return successRateColor(50);
    }

    const gradient = chart.ctx.createLinearGradient(
        0,
        scales.y.getPixelForValue(0),
        0,
        scales.y.getPixelForValue(100),
    );

    for (const stop of [0, 0.25, 0.5, 0.75, 1]) {
        gradient.addColorStop(stop, successRateColor(stop * 100));
    }

    return gradient;
}

const chartData = computed(() => ({
    labels: ratedWeeks.value.map((entry) => weekLabel(entry.week)),
    datasets: [
        {
            label: 'Success rate',
            data: ratedWeeks.value.map((entry) => entry.rate),
            borderColor: lineGradient,
            pointBackgroundColor: ratedWeeks.value.map((entry) =>
                successRateColor(entry.rate),
            ),
            pointBorderColor: ratedWeeks.value.map((entry) =>
                successRateColor(entry.rate),
            ),
            borderWidth: 3,
            pointRadius: 4,
            pointHoverRadius: 6,
            tension: 0.3,
        },
    ],
}));

const { resolvedAppearance } = useAppearance();

const chartOptions = computed(() => {
    const textColor =
        resolvedAppearance.value === 'dark'
            ? 'rgb(163, 163, 163)'
            : 'rgb(115, 115, 115)';
    const gridColor =
        resolvedAppearance.value === 'dark'
            ? 'rgba(255, 255, 255, 0.08)'
            : 'rgba(0, 0, 0, 0.06)';

    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index' as const, intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    title: (items: TooltipItem<'line'>[]) =>
                        `Week of ${items[0].label}`,
                    label: (item: TooltipItem<'line'>) =>
                        `${item.parsed.y}% of goals succeeded`,
                },
            },
        },
        scales: {
            y: {
                min: 0,
                max: 100,
                ticks: {
                    stepSize: 25,
                    color: textColor,
                    callback: (value: string | number) => `${value}%`,
                },
                grid: { color: gridColor },
            },
            x: {
                ticks: { color: textColor, maxRotation: 0, autoSkip: true },
                grid: { display: false },
            },
        },
    };
});
</script>

<template>
    <div class="h-full">
        <Line
            v-if="ratedWeeks.length >= 2"
            :data="chartData as any"
            :options="chartOptions"
        />

        <p
            v-else
            class="flex h-full items-center justify-center text-center text-sm text-muted-foreground"
        >
            Mark at least 2 weeks to see the chart.
        </p>
    </div>
</template>
