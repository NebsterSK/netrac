<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { useLocalStorage, useMediaQuery } from '@vueuse/core';
import type { ScriptableLineSegmentContext, TooltipItem } from 'chart.js';
import {
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip as ChartTooltip,
} from 'chart.js';
import {
    ArrowDown,
    ArrowUp,
    CircleCheck,
    ChevronLeft,
    ChevronRight,
    Columns3,
    EllipsisVertical,
    MessageSquare,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Line } from 'vue-chartjs';
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
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAppearance } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { destroy, index, store, update } from '@/routes/finance/net-worth';
import type { BreadcrumbItem } from '@/types';

type Statement = App.Data.Finance.StatementData;
type Asset = App.Data.Finance.AssetData;

const props = defineProps<{
    assets: Asset[];
    statements: Statement[];
    existingDates: string[];
}>();

/** Color for an asset saved without one. */
const FALLBACK_ASSET_COLOR = '#9ca3af';

function hexToRgb(hex: string): string {
    const red = parseInt(hex.slice(1, 3), 16);
    const green = parseInt(hex.slice(3, 5), 16);
    const blue = parseInt(hex.slice(5, 7), 16);

    return `rgb(${red}, ${green}, ${blue})`;
}

/** Left-to-right order of the asset columns (asset ids), saved per browser. */
const assetOrder = useLocalStorage<number[]>('net-worth.asset-order', []);

/** Assets in the saved order; assets missing from it (e.g. new ones) follow alphabetically. */
const orderedAssets = computed(() => {
    const rank = new Map(
        assetOrder.value.map((assetId, idx) => [assetId, idx]),
    );
    const unranked = Number.MAX_SAFE_INTEGER;

    return [...props.assets].sort(
        (left, right) =>
            (rank.get(left.id) ?? unranked) - (rank.get(right.id) ?? unranked),
    );
});

/** One table column / chart series per asset, in the saved column order. */
const columns = computed(() =>
    orderedAssets.value.map((asset) => ({
        id: asset.id,
        label: asset.name,
        color: hexToRgb(asset.color ?? FALLBACK_ASSET_COLOR),
    })),
);

/** Move an asset column to the given index and save the new order. */
function moveColumn(assetId: number, toIndex: number) {
    const ids = columns.value.map((col) => col.id);
    const fromIndex = ids.indexOf(assetId);

    if (
        fromIndex === -1 ||
        toIndex < 0 ||
        toIndex >= ids.length ||
        fromIndex === toIndex
    ) {
        return;
    }

    ids.splice(toIndex, 0, ...ids.splice(fromIndex, 1));
    assetOrder.value = ids;
}

function resetColumnOrder() {
    assetOrder.value = [];
}

const draggedAssetId = ref<number | null>(null);
const dropTargetAssetId = ref<number | null>(null);

function onColumnDragStart(event: DragEvent, assetId: number) {
    draggedAssetId.value = assetId;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(assetId));
    }
}

function onColumnDragOver(event: DragEvent, assetId: number) {
    if (draggedAssetId.value === null) {
        return;
    }

    event.preventDefault();
    dropTargetAssetId.value = assetId;
}

function onColumnDragLeave(assetId: number) {
    if (dropTargetAssetId.value === assetId) {
        dropTargetAssetId.value = null;
    }
}

function onColumnDrop(assetId: number) {
    if (draggedAssetId.value !== null) {
        moveColumn(
            draggedAssetId.value,
            columns.value.findIndex((col) => col.id === assetId),
        );
    }

    onColumnDragEnd();
}

function onColumnDragEnd() {
    draggedAssetId.value = null;
    dropTargetAssetId.value = null;
}

/** An asset without a recorded amount in the statement holds nothing. */
function amountOf(statement: Statement, assetId: number): number {
    return statement.amounts[assetId] ?? 0;
}

function amountsOf(statement: Statement): Record<number, number> {
    return Object.fromEntries(
        columns.value.map((col) => [col.id, amountOf(statement, col.id)]),
    );
}

const showDialog = ref(false);
const editingStatement = ref<Statement | null>(null);
const isEditing = computed(() => editingStatement.value !== null);
const pickerYear = ref(new Date().getFullYear());

const shortMonths = Array.from({ length: 12 }, (_, idx) =>
    new Date(2000, idx).toLocaleDateString('en-US', { month: 'short' }),
);

const form = useForm({
    date: `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}-01`,
    amounts: {} as Record<number, number | undefined>,
    note: '',
    is_draft: false,
});

/** Values last prefilled into the create form, to tell untouched fields from edited ones. */
const prefilledAmounts = ref<Record<number, number>>({});

function amountError(assetId: number): string | undefined {
    return (form.errors as Record<string, string | undefined>)[
        `amounts.${assetId}`
    ];
}

function roundUpToHundred(amount: number): number {
    return Math.ceil(amount / 100) * 100;
}

/**
 * Prefill the create form with the statement preceding the selected month,
 * rounded up to the nearest hundred. Fields the user already changed are kept.
 */
function prefillFromPreviousStatement() {
    const selectedKey = form.date ? monthKey(form.date) : null;

    const previous = sortedStatements.value
        .filter(
            (statement) =>
                selectedKey === null || monthKey(statement.date) < selectedKey,
        )
        .at(-1);

    const nextPrefill: Record<number, number> = {};

    for (const col of columns.value) {
        const isUntouched =
            form.amounts[col.id] === undefined ||
            form.amounts[col.id] === prefilledAmounts.value[col.id];

        const amount = previous
            ? roundUpToHundred(amountOf(previous, col.id))
            : undefined;

        if (isUntouched) {
            form.amounts[col.id] = amount;
        }

        if (amount !== undefined) {
            nextPrefill[col.id] = amount;
        }
    }

    prefilledAmounts.value = nextPrefill;
}

function openCreate() {
    editingStatement.value = null;
    form.date = `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}-01`;
    form.amounts = {};
    form.note = '';
    form.is_draft = false;
    prefilledAmounts.value = {};
    prefillFromPreviousStatement();
    pickerYear.value = new Date().getFullYear();
    showDialog.value = true;
}

function openEdit(statement: Statement) {
    editingStatement.value = statement;
    const date = new Date(statement.date);
    form.date = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-01`;
    form.amounts = amountsOf(statement);
    form.note = statement.note ?? '';
    form.is_draft = statement.is_draft;
    pickerYear.value = date.getFullYear();
    showDialog.value = true;
}

const selectedYear = computed(() => parseInt(form.date.split('-')[0]));
const selectedMonth = computed(() => parseInt(form.date.split('-')[1]));

const draftStatement = computed(
    () => props.statements.find((statement) => statement.is_draft) ?? null,
);

const latestRealStatement = computed(
    () =>
        sortedStatements.value
            .filter((statement) => !statement.is_draft)
            .at(-1) ?? null,
);

const canBeDraft = computed(() => {
    if (!isEditing.value) {
        return draftStatement.value === null;
    }

    const editing = editingStatement.value!;

    if (editing.is_draft) {
        return true;
    }

    return (
        draftStatement.value === null &&
        latestRealStatement.value?.id === editing.id
    );
});

function monthKey(date: string): string {
    return date.slice(0, 7);
}

function isMonthDisabled(month: number, year = pickerYear.value): boolean {
    const key = `${year}-${String(month).padStart(2, '0')}`;

    if (props.existingDates.some((date) => date.startsWith(key))) {
        return true;
    }

    if (form.is_draft) {
        return (
            latestRealStatement.value !== null &&
            key <= monthKey(latestRealStatement.value.date)
        );
    }

    return (
        draftStatement.value !== null &&
        key >= monthKey(draftStatement.value.date)
    );
}

function toggleDraft(checked: boolean | 'indeterminate') {
    form.is_draft = checked === true;

    if (
        !isEditing.value &&
        isMonthDisabled(selectedMonth.value, selectedYear.value)
    ) {
        form.date = '';
    }
}

function selectMonth(month: number) {
    form.date = `${pickerYear.value}-${String(month).padStart(2, '0')}-01`;

    if (!isEditing.value) {
        prefillFromPreviousStatement();
    }
}

function submitForm() {
    const url = isEditing.value
        ? update.url(editingStatement.value!.id)
        : store.url();

    const method = isEditing.value ? 'put' : 'post';

    form.transform((data) => ({
        ...data,
        note: data.note.trim() || null,
    }))[method](url, {
        onSuccess: () => {
            showDialog.value = false;
            editingStatement.value = null;
            form.reset();
        },
    });
}

function finalizeStatement(statement: Statement) {
    router.put(update.url(statement.id), {
        date: statement.date.slice(0, 10),
        amounts: amountsOf(statement),
        note: statement.note,
        is_draft: false,
    });
}

function deleteStatement(statement: Statement) {
    if (!confirm('Are you sure you want to delete this statement?')) {
        return;
    }

    router.delete(destroy.url(statement.id));
}

function formatMonth(date: string): string {
    return new Date(date).toLocaleDateString('en-US', { month: 'long' });
}

function formatAmount(amount: number): string {
    return amount.toLocaleString('fr-FR');
}

function getYear(date: string): number {
    return new Date(date).getFullYear();
}

function statementTotal(statement: Statement): number {
    return columns.value.reduce(
        (sum, col) => sum + amountOf(statement, col.id),
        0,
    );
}

const sortedStatements = computed(() =>
    [...props.statements].sort(
        (stmtA, stmtB) =>
            new Date(stmtA.date).getTime() - new Date(stmtB.date).getTime(),
    ),
);

const realStatementIds = computed(() =>
    sortedStatements.value
        .filter((statement) => !statement.is_draft)
        .map((statement) => statement.id),
);

const gainByStatementId = computed(() => {
    const gains: Record<number, number | null> = {};
    const sorted = sortedStatements.value;

    for (let idx = 0; idx < sorted.length; idx++) {
        if (idx === 0) {
            gains[sorted[idx].id] = null;
        } else {
            gains[sorted[idx].id] =
                statementTotal(sorted[idx]) - statementTotal(sorted[idx - 1]);
        }
    }

    return gains;
});

const gainPercentByStatementId = computed(() => {
    const percents: Record<number, number | null> = {};
    const sorted = sortedStatements.value;

    for (let idx = 0; idx < sorted.length; idx++) {
        if (idx === 0) {
            percents[sorted[idx].id] = null;
        } else {
            const prevTotal = statementTotal(sorted[idx - 1]);
            percents[sorted[idx].id] =
                prevTotal !== 0
                    ? ((statementTotal(sorted[idx]) - prevTotal) / prevTotal) *
                      100
                    : null;
        }
    }

    return percents;
});

const averageGain = computed(() => {
    const gains = realStatementIds.value
        .map((id) => gainByStatementId.value[id])
        .filter((val): val is number => val !== null);

    if (gains.length === 0) {
        return 0;
    }

    return Math.round(gains.reduce((sum, val) => sum + val, 0) / gains.length);
});

const averageGainPercent = computed(() => {
    const percents = realStatementIds.value
        .map((id) => gainPercentByStatementId.value[id])
        .filter((val): val is number => val !== null);

    if (percents.length === 0) {
        return 0;
    }

    return percents.reduce((sum, val) => sum + val, 0) / percents.length;
});

const columnGainByStatementId = computed(() => {
    const result: Record<
        number,
        Record<number, { gain: number; percent: number | null }>
    > = {};
    const sorted = sortedStatements.value;

    for (let idx = 0; idx < sorted.length; idx++) {
        const entry: Record<number, { gain: number; percent: number | null }> =
            {};

        for (const col of columns.value) {
            if (idx === 0) {
                entry[col.id] = { gain: 0, percent: null };
            } else {
                const prev = amountOf(sorted[idx - 1], col.id);
                const curr = amountOf(sorted[idx], col.id);
                entry[col.id] = {
                    gain: curr - prev,
                    percent: prev !== 0 ? ((curr - prev) / prev) * 100 : null,
                };
            }
        }

        result[sorted[idx].id] = entry;
    }

    return result;
});

const groupedByYear = computed(() => {
    const groups: Record<number, Statement[]> = {};

    for (const statement of sortedStatements.value) {
        const year = getYear(statement.date);
        (groups[year] ??= []).push(statement);
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
    LineElement,
    PointElement,
    Title,
    ChartTooltip,
    Legend,
    Filler,
);

/** Phones get thinner lines and smaller points so dense series stay readable. */
const isPhone = useMediaQuery('(max-width: 639px)');

const chartData = computed(() => {
    const sorted = sortedStatements.value;
    const labels = sorted.map((stmt) => {
        const date = new Date(stmt.date);

        if (date.getMonth() === 0) {
            return date.toLocaleDateString('en-US', {
                month: 'short',
                year: 'numeric',
            });
        }

        return date.toLocaleDateString('en-US', { month: 'short' });
    });

    const isDraftSegment = (ctx: ScriptableLineSegmentContext) =>
        sorted[ctx.p1DataIndex]?.is_draft ?? false;

    return {
        labels,
        datasets: columns.value.map((col) => {
            const fillColor = col.color
                .replace('rgb(', 'rgba(')
                .replace(')', ', 0.1)');
            const draftFillColor = col.color
                .replace('rgb(', 'rgba(')
                .replace(')', ', 0.03)');

            return {
                label: col.label,
                data: sorted.map((stmt) => amountOf(stmt, col.id)),
                borderColor: col.color,
                backgroundColor: fillColor,
                pointBackgroundColor: sorted.map((stmt) =>
                    stmt.is_draft ? 'transparent' : col.color,
                ),
                pointBorderWidth: sorted.map((stmt) => (stmt.is_draft ? 2 : 1)),
                borderWidth: isPhone.value ? 2 : 3,
                pointRadius: isPhone.value ? 2 : 5,
                pointHoverRadius: isPhone.value ? 3 : 5,
                fill: true,
                tension: 0,
                segment: {
                    borderDash: (ctx: ScriptableLineSegmentContext) =>
                        isDraftSegment(ctx) ? [6, 6] : undefined,
                    backgroundColor: (ctx: ScriptableLineSegmentContext) =>
                        isDraftSegment(ctx) ? draftFillColor : undefined,
                },
            };
        }),
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
        interaction: {
            mode: 'index' as const,
            intersect: false,
        },
        plugins: {
            legend: {
                display: true,
                labels: {
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 16,
                    color: textColor,
                },
            },
            tooltip: {
                reverse: true,
                callbacks: {
                    title: (items: TooltipItem<'line'>[]) => {
                        const stmt = sortedStatements.value[items[0].dataIndex];
                        const date = new Date(stmt.date);
                        const title = date.toLocaleDateString('en-US', {
                            month: 'short',
                            year: 'numeric',
                        });

                        return stmt.is_draft ? `${title} (draft)` : title;
                    },
                    label: (context: TooltipItem<'line'>) => {
                        const value = context.parsed.y ?? 0;

                        return ` ${context.dataset.label}: ${value.toLocaleString('fr-FR')}`;
                    },
                    footer: (items: TooltipItem<'line'>[]) => {
                        const total = items.reduce(
                            (sum, item) => sum + (item.parsed.y ?? 0),
                            0,
                        );

                        return `Total: ${total.toLocaleString('fr-FR')}`;
                    },
                },
            },
        },
        scales: {
            y: {
                stacked: true,
                beginAtZero: true,
                grid: {
                    color: 'rgba(128, 128, 128, 0.1)',
                },
                ticks: {
                    stepSize: 5000,
                    color: textColor,
                    callback: (value: number | string) =>
                        Number(value).toLocaleString('fr-FR'),
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
        title: 'Net Worth',
        href: index(),
    },
];
</script>

<template>
    <Head title="Net Worth" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-[4fr_6fr]">
            <Card class="self-start">
                <CardHeader>
                    <CardTitle class="leading-8">Table</CardTitle>

                    <CardAction class="flex items-center gap-2">
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    size="icon-sm"
                                    variant="outline"
                                    class="cursor-pointer"
                                    aria-label="Column order"
                                >
                                    <Columns3 class="size-3" />
                                </Button>
                            </PopoverTrigger>

                            <PopoverContent align="end" class="w-64 p-2">
                                <p
                                    class="px-2 pt-1 pb-2 text-xs text-muted-foreground"
                                >
                                    Column order. You can also drag the column
                                    headers.
                                </p>

                                <ul>
                                    <li
                                        v-for="(col, idx) in columns"
                                        :key="col.id"
                                        class="flex items-center gap-2 rounded-md py-0.5 pl-2 text-sm"
                                    >
                                        <span
                                            class="size-2.5 shrink-0 rounded-full"
                                            :style="{
                                                backgroundColor: col.color,
                                            }"
                                        />

                                        <span class="flex-1 truncate">
                                            {{ col.label }}
                                        </span>

                                        <Button
                                            size="icon-sm"
                                            variant="ghost"
                                            class="cursor-pointer"
                                            :aria-label="`Move ${col.label} earlier`"
                                            :disabled="idx === 0"
                                            @click="moveColumn(col.id, idx - 1)"
                                        >
                                            <ArrowUp class="size-4" />
                                        </Button>

                                        <Button
                                            size="icon-sm"
                                            variant="ghost"
                                            class="cursor-pointer"
                                            :aria-label="`Move ${col.label} later`"
                                            :disabled="
                                                idx === columns.length - 1
                                            "
                                            @click="moveColumn(col.id, idx + 1)"
                                        >
                                            <ArrowDown class="size-4" />
                                        </Button>
                                    </li>
                                </ul>

                                <Button
                                    v-if="assetOrder.length > 0"
                                    variant="ghost"
                                    size="sm"
                                    class="mt-1 w-full cursor-pointer"
                                    @click="resetColumnOrder()"
                                >
                                    Reset to alphabetical
                                </Button>
                            </PopoverContent>
                        </Popover>

                        <Button
                            size="icon-sm"
                            variant="outline"
                            class="cursor-pointer"
                            aria-label="Add statement"
                            @click="openCreate()"
                        >
                            <Plus class="size-3" />
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent>
                    <div
                        v-if="statements.length >= 2"
                        class="mb-3 flex items-center justify-between rounded-md bg-muted/50 px-4 py-2 text-lg"
                    >
                        <span>Average</span>

                        <div class="flex items-center gap-3">
                            <span
                                class="font-mono font-medium"
                                :class="
                                    averageGain >= 0
                                        ? 'text-green-600 dark:text-green-400'
                                        : 'text-red-600 dark:text-red-400'
                                "
                            >
                                {{ averageGain >= 0 ? '+' : '−'
                                }}{{ formatAmount(Math.abs(averageGain)) }}
                            </span>

                            <span
                                class="font-mono font-medium"
                                :class="
                                    averageGainPercent >= 0
                                        ? 'text-green-600 dark:text-green-400'
                                        : 'text-red-600 dark:text-red-400'
                                "
                            >
                                {{ averageGainPercent >= 0 ? '+' : '−'
                                }}{{
                                    Math.abs(averageGainPercent).toLocaleString(
                                        'fr-FR',
                                        {
                                            minimumFractionDigits: 1,
                                            maximumFractionDigits: 1,
                                        },
                                    )
                                }}%
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table
                            class="w-full text-left text-sm"
                            aria-label="Net worth statements"
                        >
                            <thead>
                                <tr
                                    class="border-b text-xs text-muted-foreground"
                                >
                                    <th
                                        class="w-0 pl-4 max-sm:sticky max-sm:left-0 max-sm:z-10 max-sm:bg-card"
                                    ></th>

                                    <th
                                        class="border-r px-4 py-2 max-sm:sticky max-sm:left-8 max-sm:z-10 max-sm:bg-card max-sm:shadow-[1px_0_0_var(--color-border)]"
                                    >
                                        Month
                                    </th>

                                    <th
                                        v-for="col in columns"
                                        :key="col.id"
                                        draggable="true"
                                        class="cursor-grab px-2 py-2 text-right transition-colors select-none active:cursor-grabbing"
                                        :class="{
                                            'opacity-40':
                                                draggedAssetId === col.id,
                                            'bg-muted text-foreground':
                                                dropTargetAssetId === col.id &&
                                                draggedAssetId !== col.id,
                                        }"
                                        title="Drag to reorder"
                                        @dragstart="
                                            onColumnDragStart($event, col.id)
                                        "
                                        @dragover="
                                            onColumnDragOver($event, col.id)
                                        "
                                        @dragleave="onColumnDragLeave(col.id)"
                                        @drop.prevent="onColumnDrop(col.id)"
                                        @dragend="onColumnDragEnd()"
                                    >
                                        {{ col.label }}
                                    </th>

                                    <th
                                        class="border-l bg-muted/50 px-4 py-2 text-right font-semibold"
                                    >
                                        Total
                                    </th>

                                    <th class="px-4 py-2 text-right">Gain</th>

                                    <th class="px-4 py-2 text-right">Gain %</th>
                                </tr>
                            </thead>

                            <template
                                v-for="[year, yearStatements] in groupedByYear"
                                :key="year"
                            >
                                <tbody>
                                    <tr
                                        v-for="statement in yearStatements"
                                        :key="statement.id"
                                        class="group/row border-b transition-colors last:border-0 hover:bg-muted/50"
                                        :class="{
                                            'bg-[repeating-linear-gradient(-45deg,transparent_0_6px,var(--color-muted)_6px_12px)] italic opacity-70':
                                                statement.is_draft,
                                        }"
                                    >
                                        <td
                                            class="w-0 pl-4 max-sm:sticky max-sm:left-0 max-sm:z-10 max-sm:bg-card"
                                        >
                                            <DropdownMenu>
                                                <DropdownMenuTrigger as-child>
                                                    <button
                                                        class="flex h-full cursor-pointer items-center"
                                                        aria-label="Actions"
                                                    >
                                                        <EllipsisVertical
                                                            class="size-4 text-muted-foreground"
                                                        />
                                                    </button>
                                                </DropdownMenuTrigger>

                                                <DropdownMenuContent
                                                    align="start"
                                                >
                                                    <DropdownMenuItem
                                                        v-if="
                                                            statement.is_draft
                                                        "
                                                        class="cursor-pointer"
                                                        @click="
                                                            finalizeStatement(
                                                                statement,
                                                            )
                                                        "
                                                    >
                                                        <CircleCheck
                                                            class="size-4"
                                                        />
                                                        Finalize
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem
                                                        class="cursor-pointer"
                                                        @click="
                                                            openEdit(statement)
                                                        "
                                                    >
                                                        <Pencil
                                                            class="size-4"
                                                        />
                                                        Edit
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem
                                                        class="cursor-pointer text-red-600 dark:text-red-400"
                                                        @click="
                                                            deleteStatement(
                                                                statement,
                                                            )
                                                        "
                                                    >
                                                        <Trash2
                                                            class="size-4"
                                                        />
                                                        Delete
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </td>

                                        <td
                                            class="border-r px-4 py-3 max-sm:sticky max-sm:left-8 max-sm:z-10 max-sm:bg-card max-sm:shadow-[1px_0_0_var(--color-border)]"
                                        >
                                            <span
                                                class="flex items-center gap-2 text-muted-foreground"
                                            >
                                                {{
                                                    formatMonth(statement.date)
                                                }}

                                                <TooltipProvider
                                                    v-if="statement.note"
                                                >
                                                    <Tooltip>
                                                        <TooltipTrigger
                                                            as-child
                                                        >
                                                            <MessageSquare
                                                                class="size-3.5 shrink-0 cursor-pointer"
                                                            />
                                                        </TooltipTrigger>

                                                        <TooltipContent
                                                            side="right"
                                                        >
                                                            {{ statement.note }}
                                                        </TooltipContent>
                                                    </Tooltip>
                                                </TooltipProvider>

                                                <Badge
                                                    v-if="statement.is_draft"
                                                    variant="outline"
                                                    class="border-dashed not-italic"
                                                >
                                                    Draft
                                                </Badge>
                                            </span>
                                        </td>

                                        <td
                                            v-for="col in columns"
                                            :key="col.id"
                                            class="px-2 py-3 text-right font-mono"
                                            :class="
                                                columnGainByStatementId[
                                                    statement.id
                                                ][col.id].gain < 0
                                                    ? 'text-red-600 dark:text-red-400'
                                                    : ''
                                            "
                                        >
                                            <TooltipProvider
                                                v-if="
                                                    columnGainByStatementId[
                                                        statement.id
                                                    ][col.id].percent !== null
                                                "
                                            >
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <span
                                                            class="cursor-default"
                                                        >
                                                            {{
                                                                formatAmount(
                                                                    amountOf(
                                                                        statement,
                                                                        col.id,
                                                                    ),
                                                                )
                                                            }}
                                                        </span>
                                                    </TooltipTrigger>

                                                    <TooltipContent>
                                                        <span
                                                            :class="
                                                                columnGainByStatementId[
                                                                    statement.id
                                                                ][col.id]
                                                                    .gain >= 0
                                                                    ? 'text-green-400 dark:text-green-600'
                                                                    : 'text-red-400 dark:text-red-600'
                                                            "
                                                        >
                                                            {{
                                                                columnGainByStatementId[
                                                                    statement.id
                                                                ][col.id]
                                                                    .gain >= 0
                                                                    ? '+'
                                                                    : '−'
                                                            }}{{
                                                                formatAmount(
                                                                    Math.abs(
                                                                        columnGainByStatementId[
                                                                            statement
                                                                                .id
                                                                        ][
                                                                            col
                                                                                .id
                                                                        ].gain,
                                                                    ),
                                                                )
                                                            }}
                                                            ({{
                                                                columnGainByStatementId[
                                                                    statement.id
                                                                ][col.id]
                                                                    .percent! >=
                                                                0
                                                                    ? '+'
                                                                    : '−'
                                                            }}{{
                                                                Math.abs(
                                                                    columnGainByStatementId[
                                                                        statement
                                                                            .id
                                                                    ][col.id]
                                                                        .percent!,
                                                                ).toLocaleString(
                                                                    'fr-FR',
                                                                    {
                                                                        minimumFractionDigits: 1,
                                                                        maximumFractionDigits: 1,
                                                                    },
                                                                )
                                                            }}%)
                                                        </span>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>

                                            <template v-else>
                                                {{
                                                    formatAmount(
                                                        amountOf(
                                                            statement,
                                                            col.id,
                                                        ),
                                                    )
                                                }}
                                            </template>
                                        </td>

                                        <td
                                            class="border-l bg-muted/50 px-4 py-3 text-right font-mono font-bold"
                                        >
                                            {{
                                                formatAmount(
                                                    statementTotal(statement),
                                                )
                                            }}
                                        </td>

                                        <td
                                            class="px-4 py-3 text-right font-mono"
                                            :class="
                                                gainByStatementId[
                                                    statement.id
                                                ] !== null
                                                    ? gainByStatementId[
                                                          statement.id
                                                      ]! >= 0
                                                        ? 'text-green-600 dark:text-green-400'
                                                        : 'text-red-600 dark:text-red-400'
                                                    : 'text-muted-foreground'
                                            "
                                        >
                                            <template
                                                v-if="
                                                    gainByStatementId[
                                                        statement.id
                                                    ] !== null
                                                "
                                            >
                                                {{
                                                    gainByStatementId[
                                                        statement.id
                                                    ]! >= 0
                                                        ? '+'
                                                        : '−'
                                                }}{{
                                                    formatAmount(
                                                        Math.abs(
                                                            gainByStatementId[
                                                                statement.id
                                                            ]!,
                                                        ),
                                                    )
                                                }}
                                            </template>

                                            <template v-else>—</template>
                                        </td>

                                        <td
                                            class="px-4 py-3 text-right font-mono"
                                            :class="
                                                gainPercentByStatementId[
                                                    statement.id
                                                ] !== null
                                                    ? gainPercentByStatementId[
                                                          statement.id
                                                      ]! >= 0
                                                        ? 'text-green-600 dark:text-green-400'
                                                        : 'text-red-600 dark:text-red-400'
                                                    : 'text-muted-foreground'
                                            "
                                        >
                                            <template
                                                v-if="
                                                    gainPercentByStatementId[
                                                        statement.id
                                                    ] !== null
                                                "
                                            >
                                                {{
                                                    gainPercentByStatementId[
                                                        statement.id
                                                    ]! >= 0
                                                        ? '+'
                                                        : '−'
                                                }}{{
                                                    Math.abs(
                                                        gainPercentByStatementId[
                                                            statement.id
                                                        ]!,
                                                    ).toLocaleString('fr-FR', {
                                                        minimumFractionDigits: 1,
                                                        maximumFractionDigits: 1,
                                                    })
                                                }}%
                                            </template>

                                            <template v-else>—</template>
                                        </td>
                                    </tr>

                                    <tr
                                        class="border-b bg-muted/50 last:border-0"
                                    >
                                        <td
                                            class="px-4 py-3 font-semibold"
                                            :colspan="columns.length + 5"
                                        >
                                            <span
                                                class="flex items-center gap-1 max-sm:sticky max-sm:left-4 max-sm:w-fit"
                                            >
                                                <ArrowUp class="size-4" />

                                                {{ year }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </template>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <Card class="sticky top-0 h-[90vh] self-start">
                <CardHeader>
                    <CardTitle>Chart</CardTitle>
                </CardHeader>

                <CardContent class="min-h-0 flex-1 overflow-hidden">
                    <div v-if="statements.length >= 2" class="h-full">
                        <Line
                            :data="chartData as any"
                            :options="chartOptions"
                        />
                    </div>

                    <p v-else class="text-center text-sm text-muted-foreground">
                        Add at least 2 entries to see the chart.
                    </p>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="showDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        isEditing ? 'Edit Statement' : 'Add Statement'
                    }}</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <InputError :message="form.errors.date" />

                    <div v-if="canBeDraft" class="space-y-2">
                        <Label
                            for="stmt-is-draft"
                            class="flex items-center gap-3"
                        >
                            <Checkbox
                                id="stmt-is-draft"
                                :model-value="form.is_draft"
                                :disabled="form.processing"
                                @update:model-value="toggleDraft"
                            />

                            <span>Draft (prediction)</span>
                        </Label>

                        <InputError :message="form.errors.is_draft" />
                    </div>

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

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-1 sm:gap-4">
                        <div
                            v-for="col in columns"
                            :key="col.id"
                            class="space-y-2"
                        >
                            <Label :for="`stmt-asset-${col.id}`">{{
                                col.label
                            }}</Label>

                            <Input
                                :id="`stmt-asset-${col.id}`"
                                v-model="form.amounts[col.id]"
                                type="number"
                                min="0"
                                inputmode="numeric"
                                class="[-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                :disabled="form.processing"
                            />

                            <InputError :message="amountError(col.id)" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="stmt-note">
                            Note
                            <span class="font-normal text-muted-foreground">
                                (optional)
                            </span>
                        </Label>

                        <Textarea
                            id="stmt-note"
                            v-model="form.note"
                            maxlength="255"
                            rows="2"
                            placeholder="Explains this month's balance"
                            :disabled="form.processing"
                        />

                        <InputError :message="form.errors.note" />
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
