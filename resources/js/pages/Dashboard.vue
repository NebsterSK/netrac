<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { Plugin, TooltipItem } from 'chart.js';
import {
    ArcElement,
    Chart as ChartJS,
    Legend,
    Tooltip as ChartTooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
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
import type { BreadcrumbItem } from '@/types';

type MonthlyAverage = App.Data.Finance.MonthlyAverageData;

type PeriodAverages = App.Data.Finance.PeriodAveragesData;

type Subscription = App.Data.Finance.SubscriptionExpenseData;

const props = defineProps<{
    monthlyAverages: MonthlyAverage[];
    periodAverages: PeriodAverages;
    subscriptions: Subscription[];
}>();

ChartJS.register(ArcElement, ChartTooltip, Legend);

// Known service → brand color. Matched case-insensitively; the longest matching
// key wins so "youtube music" beats "youtube".
const BRAND_COLORS: Record<string, string> = {
    netflix: '#e50914',
    youtube: '#ff0000',
    'youtube premium': '#ff0000',
    'youtube music': '#ff0000',
    'youtube tv': '#ff0000',
    spotify: '#1db954',
    disney: '#113ccf',
    'hbo max': '#7b2ff7',
    max: '#002be7',
    hulu: '#1ce783',
    'prime video': '#00a8e1',
    'amazon prime': '#00a8e1',
    'amazon music': '#00a8e1',
    amazon: '#ff9900',
    'apple tv': '#000000',
    'apple music': '#fa243c',
    'apple arcade': '#f14e50',
    'apple one': '#333333',
    icloud: '#3693f3',
    paramount: '#0064ff',
    peacock: '#05a081',
    crunchyroll: '#f47521',
    twitch: '#9146ff',
    deezer: '#feaa2d',
    tidal: '#00cfff',
    audible: '#f8991c',
    dropbox: '#0061ff',
    notion: '#111111',
    slack: '#4a154b',
    zoom: '#2d8cff',
    canva: '#00c4cc',
    figma: '#f24e1e',
    adobe: '#ff0000',
    'creative cloud': '#ff0000',
    microsoft: '#f25022',
    'microsoft 365': '#f25022',
    'office 365': '#f25022',
    'google one': '#4285f4',
    github: '#8957e5',
    'github copilot': '#8957e5',
    chatgpt: '#10a37f',
    openai: '#10a37f',
    claude: '#d97757',
    'xbox game pass': '#107c10',
    'game pass': '#107c10',
    xbox: '#107c10',
    'playstation plus': '#0070d1',
    playstation: '#0070d1',
    nintendo: '#e60012',
    steam: '#66c0f4',
    strava: '#fc4c02',
    duolingo: '#58cc02',
    headspace: '#f47d31',
    calm: '#2a6ebb',
    nordvpn: '#4687ff',
    expressvpn: '#da3940',
    patreon: '#ff424d',
    substack: '#ff6719',
    medium: '#000000',
    linkedin: '#0a66c2',
    'new york times': '#000000',
    nyt: '#000000',
};

const BRAND_KEYS = Object.keys(BRAND_COLORS).sort(
    (keyA, keyB) => keyB.length - keyA.length,
);

// Fallback categorical palette (validated via the dataviz skill) for services
// with no known brand color; the 8th slot is never cycled.
const SERIES_LIGHT = [
    '#2a78d6',
    '#1baf7a',
    '#eda100',
    '#008300',
    '#4a3aa7',
    '#e34948',
    '#e87ba4',
    '#eb6834',
];

const SERIES_DARK = [
    '#3987e5',
    '#199e70',
    '#c98500',
    '#008300',
    '#9085e9',
    '#e66767',
    '#d55181',
    '#d95926',
];

const OTHER_COLOR = '#898781';

const { resolvedAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');

const subscriptionTotal = computed(() =>
    props.subscriptions.reduce((sum, entry) => sum + entry.amount, 0),
);

/**
 * At most 8 slices: once there are more than 8 subscriptions the smallest fold
 * into a single "Other" slice so the fallback palette is never cycled.
 */
const subscriptionSlices = computed(() => {
    const entries = props.subscriptions;

    if (entries.length <= 8) {
        return entries.map((entry) => ({
            label: entry.name,
            amount: entry.amount,
        }));
    }

    const head = entries.slice(0, 7);
    const rest = entries.slice(7);
    const restTotal = rest.reduce((sum, entry) => sum + entry.amount, 0);

    return [
        ...head.map((entry) => ({ label: entry.name, amount: entry.amount })),
        { label: 'Other', amount: restTotal },
    ];
});

function brandColor(label: string): string | null {
    const normalized = label.toLowerCase().trim();

    if (BRAND_COLORS[normalized]) {
        return BRAND_COLORS[normalized];
    }

    const match = BRAND_KEYS.find((key) => normalized.includes(key));

    return match ? BRAND_COLORS[match] : null;
}

function relativeLuminance(hex: string): number {
    const value = hex.replace('#', '');
    const red = parseInt(value.slice(0, 2), 16);
    const green = parseInt(value.slice(2, 4), 16);
    const blue = parseInt(value.slice(4, 6), 16);

    return (0.299 * red + 0.587 * green + 0.114 * blue) / 255;
}

/** Lift near-black brand colors so they stay visible on the dark surface. */
function lightenForDark(hex: string, amount = 0.45): string {
    const value = hex.replace('#', '');
    const channels = [
        parseInt(value.slice(0, 2), 16),
        parseInt(value.slice(2, 4), 16),
        parseInt(value.slice(4, 6), 16),
    ].map((channel) =>
        Math.round(channel + (255 - channel) * amount)
            .toString(16)
            .padStart(2, '0'),
    );

    return `#${channels.join('')}`;
}

function sliceColor(label: string, index: number): string {
    if (label === 'Other') {
        return OTHER_COLOR;
    }

    const brand = brandColor(label);

    if (brand) {
        return isDark.value && relativeLuminance(brand) < 0.22
            ? lightenForDark(brand)
            : brand;
    }

    const palette = isDark.value ? SERIES_DARK : SERIES_LIGHT;

    return palette[index % palette.length];
}

const chartData = computed(() => ({
    labels: subscriptionSlices.value.map((slice) => slice.label),
    datasets: [
        {
            data: subscriptionSlices.value.map((slice) => slice.amount),
            backgroundColor: subscriptionSlices.value.map((slice, index) =>
                sliceColor(slice.label, index),
            ),
            borderColor: isDark.value ? '#1a1a19' : '#ffffff',
            borderWidth: 2,
        },
    ],
}));

const chartOptions = computed(() => {
    const textColor = isDark.value ? 'rgb(229, 229, 229)' : 'rgb(38, 38, 38)';
    const total = subscriptionTotal.value;

    return {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '64%',
        plugins: {
            legend: {
                display: true,
                position: 'right' as const,
                labels: {
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 12,
                    color: textColor,
                    boxWidth: 8,
                },
            },
            tooltip: {
                callbacks: {
                    label: (context: TooltipItem<'doughnut'>) => {
                        const value = context.parsed ?? 0;
                        const share =
                            total > 0 ? Math.round((value / total) * 100) : 0;

                        return ` ${value.toLocaleString('fr-FR')} (${share}%)`;
                    },
                },
            },
        },
    };
});

// Draws the monthly total in the doughnut's hole.
const centerTotalPlugin: Plugin<'doughnut'> = {
    id: 'subscriptionCenterTotal',
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
        ctx.fillText(
            subscriptionTotal.value.toLocaleString('fr-FR'),
            arc.x,
            arc.y - 7,
        );

        ctx.fillStyle = '#898781';
        ctx.font = `400 11px ${font}`;
        ctx.fillText('/ mo', arc.x, arc.y + 13);

        ctx.restore();
    },
};

const chartPlugins = [centerTotalPlugin];

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

                <Card class="aspect-video">
                    <CardHeader>
                        <CardTitle>Subscriptions</CardTitle>
                    </CardHeader>

                    <CardContent class="flex-1">
                        <div
                            v-if="subscriptions.length === 0"
                            class="flex h-full items-center justify-center px-4 text-center text-sm text-muted-foreground"
                        >
                            Add expenses to a “Subscriptions” category to see
                            the breakdown.
                        </div>

                        <div v-else class="h-full">
                            <Doughnut
                                :data="chartData"
                                :options="chartOptions"
                                :plugins="chartPlugins"
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div
                class="relative min-h-screen flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>
