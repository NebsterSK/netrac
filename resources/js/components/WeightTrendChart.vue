<script setup lang="ts">
import type { ScriptableContext, TooltipItem } from 'chart.js';
import {
    Chart as ChartJS,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip as ChartTooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import { useAppearance } from '@/composables/useAppearance';
import { successRateColor } from '@/lib/buzerlistok';
import { dayNumber, formatDay, formatWeight } from '@/lib/weight';

type Weight = App.Data.Health.WeightData;

const props = defineProps<{
    /** Oldest first. */
    weights: Weight[];
}>();

ChartJS.register(LinearScale, LineElement, PointElement, ChartTooltip);

const lightest = computed(() =>
    Math.min(...props.weights.map((weight) => weight.weight)),
);

const heaviest = computed(() =>
    Math.max(...props.weights.map((weight) => weight.weight)),
);

/** Green at the lightest entry, through yellow, to red at the heaviest. */
function weightColor(weight: number): string {
    const range = heaviest.value - lightest.value;
    const share = range === 0 ? 1 : (heaviest.value - weight) / range;

    return successRateColor(share * 100);
}

/** Paints the line along the y axis: red at the heaviest, green at the lightest. */
function lineGradient(context: ScriptableContext<'line'>) {
    const { chart } = context;
    const { chartArea, scales } = chart;

    if (!chartArea || heaviest.value === lightest.value) {
        return weightColor(heaviest.value);
    }

    const gradient = chart.ctx.createLinearGradient(
        0,
        scales.y.getPixelForValue(heaviest.value),
        0,
        scales.y.getPixelForValue(lightest.value),
    );

    for (const stop of [0, 0.25, 0.5, 0.75, 1]) {
        gradient.addColorStop(stop, successRateColor(stop * 100));
    }

    return gradient;
}

const chartData = computed(() => ({
    datasets: [
        {
            label: 'Weight',
            data: props.weights.map((weight) => ({
                x: dayNumber(weight.date),
                y: weight.weight,
            })),
            borderColor: lineGradient,
            pointBackgroundColor: props.weights.map((weight) =>
                weightColor(weight.weight),
            ),
            pointBorderColor: props.weights.map((weight) =>
                weightColor(weight.weight),
            ),
            borderWidth: 3,
            pointRadius: 4,
            pointHoverRadius: 6,
            tension: 0.2,
            clip: 8,
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
    const firstDay = dayNumber(props.weights[0]?.date ?? '1970-01-01');
    const lastDay = dayNumber(
        props.weights[props.weights.length - 1]?.date ?? '1970-01-01',
    );

    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'nearest' as const, intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    title: (items: TooltipItem<'line'>[]) =>
                        formatDay(items[0].parsed.x ?? 0),
                    label: (item: TooltipItem<'line'>) =>
                        `${formatWeight(item.parsed.y ?? 0)} kg`,
                },
            },
        },
        scales: {
            x: {
                type: 'linear' as const,
                min: firstDay,
                max: lastDay,
                afterBuildTicks: (axis: { ticks: { value: number }[] }) => {
                    axis.ticks = props.weights.map((weight) => ({
                        value: dayNumber(weight.date),
                    }));
                },
                ticks: {
                    color: textColor,
                    maxRotation: 0,
                    autoSkip: true,
                    callback: (value: string | number) =>
                        formatDay(Number(value), false),
                },
                grid: { color: gridColor },
            },
            y: {
                grace: '10%',
                ticks: {
                    color: textColor,
                    callback: (value: string | number) => `${value} kg`,
                },
                grid: { color: gridColor },
            },
        },
    };
});
</script>

<template>
    <div class="h-full">
        <Line
            v-if="weights.length >= 2"
            :data="chartData as any"
            :options="chartOptions"
        />

        <p
            v-else
            class="flex h-full items-center justify-center text-center text-sm text-muted-foreground"
        >
            Add at least 2 weight entries to see the chart.
        </p>
    </div>
</template>
