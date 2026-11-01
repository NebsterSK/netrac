<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
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
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Columns3,
    MessageSquare,
    Plus,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Line } from 'vue-chartjs';
import InputError from '@/components/InputError.vue';
import OwnerBadge from '@/components/OwnerBadge.vue';
import OwnerSelect from '@/components/OwnerSelect.vue';
import StatementActionsMenu from '@/components/StatementActionsMenu.vue';
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
import { useOwners } from '@/composables/useOwners';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { index as assetsIndex } from '@/routes/finance/assets';
import { destroy, index, store, update } from '@/routes/finance/net-worth';
import type { BreadcrumbItem } from '@/types';

type Statement = App.Data.Finance.StatementData;
type Asset = App.Data.Finance.AssetData;
type Month = App.Data.Finance.NetWorthMonthData;
type Owner = App.Data.Finance.OwnerData;

const props = defineProps<{
    assets: Asset[];
    months: Month[];
    existingMonths: Record<number, string[]>;
}>();

const { ownerFilter, defaultOwnerId, ownerById } = useOwners();

/** Under All, months combine every owner's statement and expand into them. */
const isAllOwners = computed(() => ownerFilter.value === null);

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
        ownerId: asset.user_id,
        owner: ownerById(asset.user_id),
        label: asset.name,
        color: hexToRgb(asset.color ?? FALLBACK_ASSET_COLOR),
    })),
);

type Column = (typeof columns.value)[number];

/** Asset names can repeat across owners; those get the owner's name appended in the chart. */
function chartLabel(col: Column): string {
    const isAmbiguous = columns.value.some(
        (other) => other.id !== col.id && other.label === col.label,
    );

    return isAmbiguous && col.owner
        ? `${col.label} (${col.owner.name})`
        : col.label;
}

/**
 * Move an asset column to the given index and save the new order. Only the
 * visible (filtered) columns are reordered; assets hidden by the owner filter
 * keep their saved positions.
 */
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

    const visibleIds = new Set(ids);
    const savedIds = [
        ...assetOrder.value,
        ...ids.filter((id) => !assetOrder.value.includes(id)),
    ];
    const reorderedVisible = ids[Symbol.iterator]();

    assetOrder.value = savedIds.map((id) =>
        visibleIds.has(id) ? reorderedVisible.next().value! : id,
    );
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

/** Months oldest first. */
const sortedMonths = computed(() => [...props.months].reverse());

/** Every loaded statement, oldest first. */
const allStatements = computed(() =>
    sortedMonths.value.flatMap((month) => month.statements),
);

/** Whether the asset's owner has a statement in the month. */
function hasOwnerStatement(month: Month, col: Column): boolean {
    return month.statements.some(
        (statement) => statement.user_id === col.ownerId,
    );
}

/** An asset without a recorded amount holds nothing. */
function amountOf(amounts: Record<number, number>, assetId: number): number {
    return amounts[assetId] ?? 0;
}

/** A statement's amounts for its owner's assets, as sent back to the server. */
function amountsOf(statement: Statement): Record<number, number> {
    return Object.fromEntries(
        columns.value
            .filter((col) => col.ownerId === statement.user_id)
            .map((col) => [col.id, amountOf(statement.amounts, col.id)]),
    );
}

function statementTotal(statement: Statement): number {
    return Object.values(statement.amounts).reduce(
        (sum, amount) => sum + amount,
        0,
    );
}

/**
 * Owners missing from each incomplete month: owners with a statement in an
 * earlier month but none in this one.
 */
const missingOwnersByMonth = computed(() => {
    const seenOwnerIds = new Set<number>();
    const result: Record<string, Owner[]> = {};

    for (const month of sortedMonths.value) {
        const monthOwnerIds = new Set(
            month.statements.map((statement) => statement.user_id),
        );

        result[month.month] = [...seenOwnerIds]
            .filter((ownerId) => !monthOwnerIds.has(ownerId))
            .map((ownerId) => ownerById(ownerId))
            .filter((owner): owner is Owner => owner !== null);

        monthOwnerIds.forEach((ownerId) => seenOwnerIds.add(ownerId));
    }

    return result;
});

function incompleteLabel(month: Month): string {
    const names = (missingOwnersByMonth.value[month.month] ?? []).map(
        (owner) => owner.name,
    );

    return names.length > 0 ? `Waiting for ${names.join(', ')}` : 'Incomplete';
}

/** Months counted in the averages: final and with every required owner's statement. */
const settledMonths = computed(() =>
    sortedMonths.value.filter((month) => !month.is_draft && month.is_complete),
);

const averageGain = computed(() => {
    const gains = settledMonths.value
        .map((month) => month.gain)
        .filter((val): val is number => val !== null);

    if (gains.length === 0) {
        return 0;
    }

    return Math.round(gains.reduce((sum, val) => sum + val, 0) / gains.length);
});

const averageGainPercent = computed(() => {
    const percents = settledMonths.value
        .map((month) => month.gain_percent)
        .filter((val): val is number => val !== null);

    if (percents.length === 0) {
        return 0;
    }

    return percents.reduce((sum, val) => sum + val, 0) / percents.length;
});

type ColumnGain = { gain: number; percent: number | null } | null;

/** Per-asset change against the previous month; null when either month lacks the owner's statement. */
const columnGainByMonth = computed(() => {
    const result: Record<string, Record<number, ColumnGain>> = {};
    const sorted = sortedMonths.value;

    for (let idx = 0; idx < sorted.length; idx++) {
        const entry: Record<number, ColumnGain> = {};

        for (const col of columns.value) {
            const previous = sorted[idx - 1];

            if (
                !previous ||
                !hasOwnerStatement(previous, col) ||
                !hasOwnerStatement(sorted[idx], col)
            ) {
                entry[col.id] = null;

                continue;
            }

            const prev = amountOf(previous.amounts, col.id);
            const curr = amountOf(sorted[idx].amounts, col.id);

            entry[col.id] = {
                gain: curr - prev,
                percent: prev !== 0 ? ((curr - prev) / prev) * 100 : null,
            };
        }

        result[sorted[idx].month] = entry;
    }

    return result;
});

const groupedByYear = computed(() => {
    const groups = new Map<string, Month[]>();

    for (const month of props.months) {
        const year = month.month.slice(0, 4);

        if (!groups.has(year)) {
            groups.set(year, []);
        }

        groups.get(year)!.push(month);
    }

    return [...groups.entries()];
});

const expandedMonths = ref(new Set<string>());

function toggleMonth(month: string) {
    if (expandedMonths.value.has(month)) {
        expandedMonths.value.delete(month);
    } else {
        expandedMonths.value.add(month);
    }
}

function formatMonth(month: string): string {
    const [year, monthNumber] = month.split('-').map(Number);

    return new Date(year, monthNumber - 1).toLocaleDateString('en-US', {
        month: 'long',
    });
}

function formatAmount(amount: number): string {
    return amount.toLocaleString('fr-FR');
}

function formatSignedAmount(amount: number): string {
    return `${amount >= 0 ? '+' : '−'}${formatAmount(Math.abs(amount))}`;
}

function formatSignedPercent(percent: number): string {
    return `${percent >= 0 ? '+' : '−'}${Math.abs(percent).toLocaleString(
        'fr-FR',
        {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1,
        },
    )}%`;
}

function gainClass(value: number | null): string {
    if (value === null) {
        return 'text-muted-foreground';
    }

    return value >= 0
        ? 'text-green-600 dark:text-green-400'
        : 'text-red-600 dark:text-red-400';
}

const showDialog = ref(false);
const editingStatement = ref<Statement | null>(null);
const isEditing = computed(() => editingStatement.value !== null);
const pickerYear = ref(new Date().getFullYear());

const shortMonths = Array.from({ length: 12 }, (_, idx) =>
    new Date(2000, idx).toLocaleDateString('en-US', { month: 'short' }),
);

function currentMonthDate(): string {
    return `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}-01`;
}

const form = useForm({
    user_id: null as number | null,
    date: currentMonthDate(),
    amounts: {} as Record<number, number | undefined>,
    note: '',
    is_draft: false,
});

/** The asset inputs: the form owner's assets only. */
const formColumns = computed(() =>
    columns.value.filter((col) => col.ownerId === form.user_id),
);

/** The form owner's statements, oldest first. */
const ownerStatements = computed(() =>
    allStatements.value.filter(
        (statement) => statement.user_id === form.user_id,
    ),
);

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
 * Prefill the create form with the owner's statement preceding the selected
 * month, rounded up to the nearest hundred. Fields the user already changed are kept.
 */
function prefillFromPreviousStatement() {
    const selectedKey = form.date ? monthKey(form.date) : null;

    const previous = ownerStatements.value
        .filter(
            (statement) =>
                selectedKey === null || monthKey(statement.date) < selectedKey,
        )
        .at(-1);

    const nextPrefill: Record<number, number> = {};

    for (const col of formColumns.value) {
        const isUntouched =
            form.amounts[col.id] === undefined ||
            form.amounts[col.id] === prefilledAmounts.value[col.id];

        const amount = previous
            ? roundUpToHundred(amountOf(previous.amounts, col.id))
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

/** Reset the owner-specific parts of the create form for a newly picked owner. */
function onOwnerChange() {
    if (isEditing.value) {
        return;
    }

    form.amounts = {};
    prefilledAmounts.value = {};

    if (
        !canBeDraft.value ||
        (form.date && isMonthDisabled(selectedMonth.value, selectedYear.value))
    ) {
        form.is_draft = false;
    }

    if (form.date && isMonthDisabled(selectedMonth.value, selectedYear.value)) {
        form.date = '';
    }

    prefillFromPreviousStatement();
}

function selectOwner(ownerId: number | null) {
    form.user_id = ownerId;
    onOwnerChange();
}

function openCreate() {
    editingStatement.value = null;
    form.clearErrors();
    form.user_id = defaultOwnerId.value;
    form.date = currentMonthDate();
    form.note = '';
    form.is_draft = false;
    pickerYear.value = new Date().getFullYear();
    onOwnerChange();
    showDialog.value = true;
}

function openEdit(statement: Statement) {
    editingStatement.value = statement;
    form.clearErrors();
    form.user_id = statement.user_id;
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

/** The form owner's draft; drafts are limited to one per owner. */
const draftStatement = computed(
    () => ownerStatements.value.find((statement) => statement.is_draft) ?? null,
);

const latestRealStatement = computed(
    () =>
        ownerStatements.value
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

/** Months the form owner already has a statement for. */
const ownerExistingMonths = computed(() =>
    form.user_id === null ? [] : (props.existingMonths[form.user_id] ?? []),
);

function isMonthDisabled(month: number, year = pickerYear.value): boolean {
    const key = `${year}-${String(month).padStart(2, '0')}`;

    if (ownerExistingMonths.value.includes(key)) {
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
        form.date &&
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
    const assetIds = formColumns.value.map((col) => col.id);

    form.transform(({ user_id: ownerId, ...data }) => ({
        ...data,
        ...(isEditing.value ? {} : { user_id: ownerId }),
        amounts: Object.fromEntries(
            assetIds.map((assetId) => [assetId, data.amounts[assetId]]),
        ),
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

/** Drafts and incomplete months are drawn dashed with hollow points. */
function isProvisional(month: Month | undefined): boolean {
    return month !== undefined && (month.is_draft || !month.is_complete);
}

const chartData = computed(() => {
    const sorted = sortedMonths.value;
    const labels = sorted.map((month) => {
        const [year, monthNumber] = month.month.split('-').map(Number);
        const date = new Date(year, monthNumber - 1);

        if (date.getMonth() === 0) {
            return date.toLocaleDateString('en-US', {
                month: 'short',
                year: 'numeric',
            });
        }

        return date.toLocaleDateString('en-US', { month: 'short' });
    });

    const isProvisionalSegment = (ctx: ScriptableLineSegmentContext) =>
        isProvisional(sorted[ctx.p1DataIndex]);

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
                label: chartLabel(col),
                data: sorted.map((month) => amountOf(month.amounts, col.id)),
                borderColor: col.color,
                backgroundColor: fillColor,
                pointBackgroundColor: sorted.map((month) =>
                    isProvisional(month) ? 'transparent' : col.color,
                ),
                pointBorderWidth: sorted.map((month) =>
                    isProvisional(month) ? 2 : 1,
                ),
                borderWidth: isPhone.value ? 2 : 3,
                pointRadius: isPhone.value ? 2 : 5,
                pointHoverRadius: isPhone.value ? 3 : 5,
                fill: true,
                tension: 0,
                segment: {
                    borderDash: (ctx: ScriptableLineSegmentContext) =>
                        isProvisionalSegment(ctx) ? [6, 6] : undefined,
                    backgroundColor: (ctx: ScriptableLineSegmentContext) =>
                        isProvisionalSegment(ctx) ? draftFillColor : undefined,
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
                        const month = sortedMonths.value[items[0].dataIndex];
                        const [year, monthNumber] = month.month
                            .split('-')
                            .map(Number);
                        const title = new Date(
                            year,
                            monthNumber - 1,
                        ).toLocaleDateString('en-US', {
                            month: 'short',
                            year: 'numeric',
                        });

                        if (!month.is_complete) {
                            return `${title} (${incompleteLabel(month).toLowerCase()})`;
                        }

                        return month.is_draft ? `${title} (draft)` : title;
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
            <Card class="min-w-0 self-start">
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

                            <PopoverContent align="end" class="w-72 p-2">
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

                                        <OwnerBadge
                                            v-if="isAllOwners && col.owner"
                                            :owner="col.owner"
                                        />

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
                        v-if="months.length >= 2"
                        class="mb-3 flex items-center justify-between rounded-md bg-muted/50 px-4 py-2 text-lg"
                    >
                        <span>Average</span>

                        <div class="flex items-center gap-3">
                            <span
                                class="font-mono font-medium"
                                :class="gainClass(averageGain)"
                            >
                                {{ formatSignedAmount(averageGain) }}
                            </span>

                            <span
                                class="font-mono font-medium"
                                :class="gainClass(averageGainPercent)"
                            >
                                {{ formatSignedPercent(averageGainPercent) }}
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
                                        class="cursor-grab px-2 py-2 text-right align-top transition-colors select-none active:cursor-grabbing"
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
                                        <span
                                            class="inline-flex items-center gap-1.5 whitespace-nowrap"
                                        >
                                            <span
                                                v-if="isAllOwners && col.owner"
                                                class="size-2 shrink-0 rounded-full sm:hidden"
                                                :style="{
                                                    backgroundColor:
                                                        col.owner.color,
                                                }"
                                                :title="col.owner.name"
                                            />

                                            <span
                                                v-if="isAllOwners && col.owner"
                                                class="sr-only"
                                            >
                                                {{ col.owner.name }}:
                                            </span>

                                            {{ col.label }}
                                        </span>

                                        <span
                                            v-if="isAllOwners && col.owner"
                                            class="mt-1 hidden justify-end sm:flex"
                                            aria-hidden="true"
                                        >
                                            <OwnerBadge :owner="col.owner" />
                                        </span>
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
                                v-for="[year, yearMonths] in groupedByYear"
                                :key="year"
                            >
                                <tbody>
                                    <template
                                        v-for="month in yearMonths"
                                        :key="month.month"
                                    >
                                        <tr
                                            class="group/row border-b transition-colors last:border-0 hover:bg-muted/50"
                                            :class="{
                                                'bg-[repeating-linear-gradient(-45deg,transparent_0_6px,var(--color-muted)_6px_12px)]':
                                                    month.is_draft ||
                                                    !month.is_complete,
                                                'italic opacity-70':
                                                    month.is_draft,
                                                'cursor-pointer': isAllOwners,
                                                'bg-muted/30':
                                                    isAllOwners &&
                                                    expandedMonths.has(
                                                        month.month,
                                                    ),
                                            }"
                                            @click="
                                                isAllOwners &&
                                                toggleMonth(month.month)
                                            "
                                        >
                                            <td
                                                class="w-0 pl-4 max-sm:sticky max-sm:left-0 max-sm:z-10 max-sm:bg-card"
                                            >
                                                <ChevronDown
                                                    v-if="isAllOwners"
                                                    class="size-4 text-muted-foreground transition-transform"
                                                    :class="{
                                                        '-rotate-90':
                                                            !expandedMonths.has(
                                                                month.month,
                                                            ),
                                                    }"
                                                    aria-hidden="true"
                                                />

                                                <StatementActionsMenu
                                                    v-else-if="
                                                        month.statements[0]
                                                    "
                                                    :statement="
                                                        month.statements[0]
                                                    "
                                                    @finalize="
                                                        finalizeStatement(
                                                            month.statements[0],
                                                        )
                                                    "
                                                    @edit="
                                                        openEdit(
                                                            month.statements[0],
                                                        )
                                                    "
                                                    @delete="
                                                        deleteStatement(
                                                            month.statements[0],
                                                        )
                                                    "
                                                />
                                            </td>

                                            <td
                                                class="border-r px-4 py-3 max-sm:sticky max-sm:left-8 max-sm:z-10 max-sm:bg-card max-sm:shadow-[1px_0_0_var(--color-border)]"
                                            >
                                                <button
                                                    v-if="isAllOwners"
                                                    type="button"
                                                    class="-mx-2 -my-1.5 flex min-h-9 cursor-pointer flex-wrap items-center gap-x-2 gap-y-1 rounded-md px-2 py-1.5 text-left text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                    :aria-expanded="
                                                        expandedMonths.has(
                                                            month.month,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        formatMonth(month.month)
                                                    }}

                                                    <Badge
                                                        v-if="month.is_draft"
                                                        variant="outline"
                                                        class="border-dashed not-italic"
                                                    >
                                                        Draft
                                                    </Badge>

                                                    <span
                                                        v-if="
                                                            !month.is_complete
                                                        "
                                                        class="size-2 shrink-0 rounded-full bg-amber-500 sm:hidden"
                                                        :title="
                                                            incompleteLabel(
                                                                month,
                                                            )
                                                        "
                                                    >
                                                        <span class="sr-only">
                                                            {{
                                                                incompleteLabel(
                                                                    month,
                                                                )
                                                            }}
                                                        </span>
                                                    </span>

                                                    <Badge
                                                        v-if="
                                                            !month.is_complete
                                                        "
                                                        variant="outline"
                                                        class="hidden border-dashed border-amber-500/60 text-amber-700 not-italic sm:inline-flex dark:text-amber-400"
                                                    >
                                                        {{
                                                            incompleteLabel(
                                                                month,
                                                            )
                                                        }}
                                                    </Badge>
                                                </button>

                                                <span
                                                    v-else
                                                    class="flex items-center gap-2 text-muted-foreground"
                                                >
                                                    {{
                                                        formatMonth(month.month)
                                                    }}

                                                    <TooltipProvider
                                                        v-if="
                                                            month.statements[0]
                                                                ?.note
                                                        "
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
                                                                {{
                                                                    month
                                                                        .statements[0]
                                                                        .note
                                                                }}
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>

                                                    <Badge
                                                        v-if="month.is_draft"
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
                                                    (columnGainByMonth[
                                                        month.month
                                                    ]?.[col.id]?.gain ?? 0) < 0
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : ''
                                                "
                                            >
                                                <span
                                                    v-if="
                                                        !hasOwnerStatement(
                                                            month,
                                                            col,
                                                        )
                                                    "
                                                    class="text-muted-foreground"
                                                >
                                                    —
                                                </span>

                                                <TooltipProvider
                                                    v-else-if="
                                                        columnGainByMonth[
                                                            month.month
                                                        ]?.[col.id]?.percent !=
                                                        null
                                                    "
                                                >
                                                    <Tooltip>
                                                        <TooltipTrigger
                                                            as-child
                                                        >
                                                            <span
                                                                class="cursor-default"
                                                            >
                                                                {{
                                                                    formatAmount(
                                                                        amountOf(
                                                                            month.amounts,
                                                                            col.id,
                                                                        ),
                                                                    )
                                                                }}
                                                            </span>
                                                        </TooltipTrigger>

                                                        <TooltipContent>
                                                            <span
                                                                :class="
                                                                    columnGainByMonth[
                                                                        month
                                                                            .month
                                                                    ][col.id]!
                                                                        .gain >=
                                                                    0
                                                                        ? 'text-green-400 dark:text-green-600'
                                                                        : 'text-red-400 dark:text-red-600'
                                                                "
                                                            >
                                                                {{
                                                                    formatSignedAmount(
                                                                        columnGainByMonth[
                                                                            month
                                                                                .month
                                                                        ][
                                                                            col
                                                                                .id
                                                                        ]!.gain,
                                                                    )
                                                                }}
                                                                ({{
                                                                    formatSignedPercent(
                                                                        columnGainByMonth[
                                                                            month
                                                                                .month
                                                                        ][
                                                                            col
                                                                                .id
                                                                        ]!
                                                                            .percent!,
                                                                    )
                                                                }})
                                                            </span>
                                                        </TooltipContent>
                                                    </Tooltip>
                                                </TooltipProvider>

                                                <template v-else>
                                                    {{
                                                        formatAmount(
                                                            amountOf(
                                                                month.amounts,
                                                                col.id,
                                                            ),
                                                        )
                                                    }}
                                                </template>
                                            </td>

                                            <td
                                                class="border-l bg-muted/50 px-4 py-3 text-right font-mono font-bold"
                                            >
                                                {{ formatAmount(month.total) }}
                                            </td>

                                            <td
                                                class="px-4 py-3 text-right font-mono"
                                                :class="gainClass(month.gain)"
                                            >
                                                {{
                                                    month.gain !== null
                                                        ? formatSignedAmount(
                                                              month.gain,
                                                          )
                                                        : '—'
                                                }}
                                            </td>

                                            <td
                                                class="px-4 py-3 text-right font-mono"
                                                :class="
                                                    gainClass(
                                                        month.gain_percent,
                                                    )
                                                "
                                            >
                                                {{
                                                    month.gain_percent !== null
                                                        ? formatSignedPercent(
                                                              month.gain_percent,
                                                          )
                                                        : '—'
                                                }}
                                            </td>
                                        </tr>

                                        <template
                                            v-if="
                                                isAllOwners &&
                                                expandedMonths.has(month.month)
                                            "
                                        >
                                            <tr
                                                v-for="statement in month.statements"
                                                :key="statement.id"
                                                class="border-b bg-muted/30 text-xs"
                                                :class="{
                                                    'bg-[repeating-linear-gradient(-45deg,transparent_0_6px,var(--color-muted)_6px_12px)] italic opacity-70':
                                                        statement.is_draft,
                                                }"
                                            >
                                                <td
                                                    class="w-0 pl-4 max-sm:sticky max-sm:left-0 max-sm:z-10 max-sm:bg-card"
                                                >
                                                    <StatementActionsMenu
                                                        :statement="statement"
                                                        :label="`Actions for ${ownerById(statement.user_id)?.name ?? 'statement'}`"
                                                        @finalize="
                                                            finalizeStatement(
                                                                statement,
                                                            )
                                                        "
                                                        @edit="
                                                            openEdit(statement)
                                                        "
                                                        @delete="
                                                            deleteStatement(
                                                                statement,
                                                            )
                                                        "
                                                    />
                                                </td>

                                                <td
                                                    class="border-r py-2 pr-4 pl-6 max-sm:sticky max-sm:left-8 max-sm:z-10 max-sm:bg-card max-sm:shadow-[1px_0_0_var(--color-border)]"
                                                >
                                                    <span
                                                        class="flex items-center gap-2"
                                                    >
                                                        <OwnerBadge
                                                            v-if="
                                                                ownerById(
                                                                    statement.user_id,
                                                                )
                                                            "
                                                            :owner="
                                                                ownerById(
                                                                    statement.user_id,
                                                                )!
                                                            "
                                                            class="not-italic"
                                                        />

                                                        <TooltipProvider
                                                            v-if="
                                                                statement.note
                                                            "
                                                        >
                                                            <Tooltip>
                                                                <TooltipTrigger
                                                                    as-child
                                                                >
                                                                    <MessageSquare
                                                                        class="size-3.5 shrink-0 cursor-pointer text-muted-foreground"
                                                                    />
                                                                </TooltipTrigger>

                                                                <TooltipContent
                                                                    side="right"
                                                                >
                                                                    {{
                                                                        statement.note
                                                                    }}
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        </TooltipProvider>

                                                        <Badge
                                                            v-if="
                                                                statement.is_draft
                                                            "
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
                                                    class="px-2 py-2 text-right font-mono text-muted-foreground"
                                                >
                                                    <template
                                                        v-if="
                                                            col.ownerId ===
                                                            statement.user_id
                                                        "
                                                    >
                                                        {{
                                                            formatAmount(
                                                                amountOf(
                                                                    statement.amounts,
                                                                    col.id,
                                                                ),
                                                            )
                                                        }}
                                                    </template>
                                                </td>

                                                <td
                                                    class="border-l px-4 py-2 text-right font-mono font-semibold"
                                                >
                                                    {{
                                                        formatAmount(
                                                            statementTotal(
                                                                statement,
                                                            ),
                                                        )
                                                    }}
                                                </td>

                                                <td colspan="2"></td>
                                            </tr>
                                        </template>
                                    </template>

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
                    <div v-if="months.length >= 2" class="h-full">
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
            <DialogContent class="max-h-[90dvh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{
                        isEditing ? 'Edit Statement' : 'Add Statement'
                    }}</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitForm">
                    <fieldset :disabled="form.processing" class="space-y-4">
                        <div class="space-y-2">
                            <Label :for="isEditing ? undefined : 'stmt-owner'">
                                Owner
                            </Label>

                            <div v-if="isEditing">
                                <OwnerBadge
                                    v-if="ownerById(form.user_id)"
                                    :owner="ownerById(form.user_id)!"
                                />
                            </div>

                            <OwnerSelect
                                v-else
                                id="stmt-owner"
                                :model-value="form.user_id"
                                :disabled="form.processing || !isAllOwners"
                                @update:model-value="selectOwner"
                            />

                            <InputError :message="form.errors.user_id" />
                        </div>

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
                                <div
                                    class="mb-3 flex items-center justify-between"
                                >
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        class="cursor-pointer"
                                        aria-label="Previous year"
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
                                        aria-label="Next year"
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

                        <p
                            v-if="formColumns.length === 0"
                            class="rounded-md border border-dashed p-3 text-sm text-muted-foreground"
                        >
                            This owner has no assets yet.

                            <Link
                                :href="assetsIndex()"
                                class="font-medium text-foreground underline underline-offset-4"
                            >
                                Add assets
                            </Link>
                        </p>

                        <div
                            v-else
                            class="grid grid-cols-2 gap-3 sm:grid-cols-1 sm:gap-4"
                        >
                            <div
                                v-for="col in formColumns"
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

                        <InputError :message="form.errors.amounts" />

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
                                :disabled="
                                    form.processing || formColumns.length === 0
                                "
                            >
                                <Spinner v-if="form.processing" />
                                {{ isEditing ? 'Update' : 'Create' }}
                            </Button>
                        </DialogFooter>
                    </fieldset>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
