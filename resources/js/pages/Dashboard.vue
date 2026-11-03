<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useEventListener } from '@vueuse/core';
import { GripVertical } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import BuzerlistokRateChart from '@/components/BuzerlistokRateChart.vue';
import ExpenseDoughnut from '@/components/ExpenseDoughnut.vue';
import WeightTrendChart from '@/components/WeightTrendChart.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useAppearance } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import { seriesColor } from '@/lib/chartColors';
import { dashboard } from '@/routes';
import { reorder } from '@/routes/finance/expense-categories';
import type { BreadcrumbItem } from '@/types';

type PeriodAverages = App.Data.Finance.PeriodAveragesData;

type ExpenseCategoryBreakdown = App.Data.Finance.ExpenseCategoryBreakdownData;

const props = defineProps<{
    periodAverages: PeriodAverages;
    expenseCategories: ExpenseCategoryBreakdown[];
    buzerlistokWeeks: App.Data.Buzerlistok.WeekData[];
    latestWeights: App.Data.Health.WeightData[];
}>();

const { resolvedAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');

const orderedCategories = ref([...props.expenseCategories]);

watch(
    () => props.expenseCategories,
    (categories) => {
        orderedCategories.value = [...categories];
    },
);

/**
 * A category's own color wins; otherwise the fallback follows creation order
 * so it stays put when cards are reordered.
 */
const colorByCategoryId = computed(() => {
    const categories = [...props.expenseCategories].sort(
        (categoryA, categoryB) => categoryA.id - categoryB.id,
    );

    return new Map(
        categories.map((category, index) => [
            category.id,
            category.color ?? seriesColor(index, isDark.value),
        ]),
    );
});

const grandTotalSlices = computed(() =>
    orderedCategories.value.map((category) => ({
        label: category.name,
        amount: category.total,
        color: colorByCategoryId.value.get(category.id),
    })),
);

const draggedCategoryId = ref<number | null>(null);
const grabbedCategoryId = ref<number | null>(null);

useEventListener(window, 'pointerup', () => {
    grabbedCategoryId.value = null;
});

function startDrag(event: DragEvent, categoryId: number) {
    draggedCategoryId.value = categoryId;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }
}

function dragOver(targetCategoryId: number) {
    const draggedId = draggedCategoryId.value;

    if (draggedId === null || draggedId === targetCategoryId) {
        return;
    }

    const categories = [...orderedCategories.value];
    const fromIndex = categories.findIndex((item) => item.id === draggedId);
    const toIndex = categories.findIndex(
        (item) => item.id === targetCategoryId,
    );
    const [moved] = categories.splice(fromIndex, 1);
    categories.splice(toIndex, 0, moved);
    orderedCategories.value = categories;
}

function endDrag() {
    draggedCategoryId.value = null;
    grabbedCategoryId.value = null;

    const ids = orderedCategories.value.map((category) => category.id);
    const originalIds = props.expenseCategories.map((category) => category.id);

    if (ids.every((id, index) => id === originalIds[index])) {
        return;
    }

    router.put(
        reorder.url(),
        { ids },
        { preserveScroll: true, preserveState: true },
    );
}

function categorySlices(category: ExpenseCategoryBreakdown) {
    return category.expenses.map((expense) => ({
        label: expense.name,
        amount: expense.amount,
        color: expense.color ?? undefined,
    }));
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

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
                <Card class="h-64 md:aspect-video md:h-auto">
                    <CardHeader>
                        <CardTitle>Buzerlistok Success Rate</CardTitle>
                    </CardHeader>

                    <CardContent class="min-h-0 flex-1">
                        <BuzerlistokRateChart :weeks="buzerlistokWeeks" />
                    </CardContent>
                </Card>

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

                <Card class="h-64 md:aspect-video md:h-auto">
                    <CardHeader>
                        <CardTitle>Weight</CardTitle>
                    </CardHeader>

                    <CardContent class="min-h-0 flex-1">
                        <WeightTrendChart :weights="latestWeights" />
                    </CardContent>
                </Card>
            </div>

            <div
                v-if="expenseCategories.length > 0"
                class="grid gap-4 sm:grid-cols-2 xl:auto-cols-fr xl:grid-flow-col xl:grid-cols-none"
            >
                <Card>
                    <CardHeader>
                        <CardTitle>All Expenses</CardTitle>
                    </CardHeader>

                    <CardContent>
                        <ExpenseDoughnut :slices="grandTotalSlices" />
                    </CardContent>
                </Card>

                <Card
                    v-for="category in orderedCategories"
                    :key="category.id"
                    :draggable="grabbedCategoryId === category.id"
                    class="transition-opacity"
                    :class="{
                        'opacity-40 ring-2 ring-primary/40':
                            draggedCategoryId === category.id,
                    }"
                    @dragstart="startDrag($event, category.id)"
                    @dragover.prevent="dragOver(category.id)"
                    @drop.prevent
                    @dragend="endDrag"
                >
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <span
                                class="size-2.5 rounded-full"
                                :style="{
                                    backgroundColor: colorByCategoryId.get(
                                        category.id,
                                    ),
                                }"
                            />

                            {{ category.name }}

                            <GripVertical
                                class="ml-auto size-4 cursor-grab text-muted-foreground active:cursor-grabbing"
                                aria-label="Drag to reorder"
                                @pointerdown="grabbedCategoryId = category.id"
                            />
                        </CardTitle>
                    </CardHeader>

                    <CardContent>
                        <ExpenseDoughnut :slices="categorySlices(category)" />
                    </CardContent>
                </Card>
            </div>

            <Card v-else>
                <CardContent
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    Add expenses to see the breakdown by category.
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
