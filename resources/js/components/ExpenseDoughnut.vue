<script setup lang="ts">
import type { Plugin, TooltipItem } from 'chart.js';
import {
    ArcElement,
    Chart as ChartJS,
    Legend,
    Tooltip as ChartTooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { useAppearance } from '@/composables/useAppearance';
import { sliceColor } from '@/lib/chartColors';

export type DoughnutSlice = {
    label: string;
    amount: number;
    color?: string;
};

const props = defineProps<{
    slices: DoughnutSlice[];
}>();

ChartJS.register(ArcElement, ChartTooltip, Legend);

const { resolvedAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');

const total = computed(() =>
    props.slices.reduce((sum, slice) => sum + slice.amount, 0),
);

/**
 * At most 8 slices: once there are more than 8 entries the smallest fold into
 * a single "Other" slice so the fallback palette is never cycled.
 */
const visibleSlices = computed(() => {
    if (props.slices.length <= 8) {
        return props.slices;
    }

    const head = props.slices.slice(0, 7);
    const restTotal = props.slices
        .slice(7)
        .reduce((sum, slice) => sum + slice.amount, 0);

    return [...head, { label: 'Other', amount: restTotal }];
});

const coloredSlices = computed(() =>
    visibleSlices.value.map((slice, index) => ({
        ...slice,
        color: slice.color ?? sliceColor(slice.label, index, isDark.value),
    })),
);

const chartData = computed(() => ({
    labels: coloredSlices.value.map((slice) => slice.label),
    datasets: [
        {
            data: coloredSlices.value.map((slice) => slice.amount),
            backgroundColor: coloredSlices.value.map((slice) => slice.color),
            borderColor: isDark.value ? '#1a1a19' : '#ffffff',
            borderWidth: 2,
        },
    ],
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '64%',
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            callbacks: {
                label: (context: TooltipItem<'doughnut'>) => {
                    const value = context.parsed ?? 0;
                    const share =
                        total.value > 0
                            ? Math.round((value / total.value) * 100)
                            : 0;

                    return ` ${value.toLocaleString('fr-FR')} (${share}%)`;
                },
            },
        },
    },
}));

// Draws the monthly total in the doughnut's hole.
const centerTotalPlugin: Plugin<'doughnut'> = {
    id: 'expenseCenterTotal',
    afterDraw(chart) {
        const arc = chart.getDatasetMeta(0).data[0] as
            | { x: number; y: number }
            | undefined;

        if (!arc) {
            return;
        }

        const { ctx } = chart;
        const font = 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';

        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        ctx.fillStyle = isDark.value ? '#ffffff' : '#0b0b0b';
        ctx.font = `600 22px ${font}`;
        ctx.fillText(total.value.toLocaleString('fr-FR'), arc.x, arc.y - 7);

        ctx.fillStyle = '#898781';
        ctx.font = `400 11px ${font}`;
        ctx.fillText('/ mo', arc.x, arc.y + 13);

        ctx.restore();
    },
};

const chartPlugins = [centerTotalPlugin];
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="h-48">
            <Doughnut
                :data="chartData"
                :options="chartOptions"
                :plugins="chartPlugins"
            />
        </div>

        <ul class="space-y-1.5 text-sm">
            <li
                v-for="slice in coloredSlices"
                :key="slice.label"
                class="flex items-center gap-2"
            >
                <span
                    class="size-2.5 shrink-0 rounded-full"
                    :style="{ backgroundColor: slice.color }"
                />

                <span class="min-w-0 flex-1 truncate" :title="slice.label">
                    {{ slice.label }}
                </span>

                <span class="font-mono text-muted-foreground tabular-nums">
                    {{ slice.amount.toLocaleString('fr-FR') }}
                </span>
            </li>
        </ul>
    </div>
</template>
