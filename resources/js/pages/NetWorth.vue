<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import type { TooltipItem } from 'chart.js';
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
    ArrowUp,
    ChevronLeft,
    ChevronRight,
    EllipsisVertical,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Line } from 'vue-chartjs';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { destroy, index, store, update } from '@/routes/net-worth';
import type { BreadcrumbItem } from '@/types';

interface Statement {
    id: number;
    date: string;
    account: number;
    legacy_upgrade: number;
    uniqa_sds: number;
    uniqa_dds: number;
    finax: number;
    trading212: number;
}

const columns = [
    { key: 'account', label: 'Account', color: 'rgb(156, 163, 175)' },
    {
        key: 'legacy_upgrade',
        label: 'Legacy Upgrade',
        color: 'rgb(5, 89, 210)',
    },
    { key: 'uniqa_sds', label: 'Uniqa SDS', color: 'rgb(0, 92, 169)' },
    { key: 'uniqa_dds', label: 'Uniqa DDS', color: 'rgb(0, 92, 169)' },
    { key: 'finax', label: 'Finax', color: 'rgb(203, 231, 255)' },
    { key: 'trading212', label: 'Trading 212', color: 'rgb(0, 171, 224)' },
] as const;

const props = defineProps<{
    statements: Statement[];
    existingDates: string[];
}>();

const showDialog = ref(false);
const editingStatement = ref<Statement | null>(null);
const isEditing = computed(() => editingStatement.value !== null);
const pickerYear = ref(new Date().getFullYear());

const shortMonths = Array.from({ length: 12 }, (_, idx) =>
    new Date(2000, idx).toLocaleDateString('en-US', { month: 'short' }),
);

const form = useForm({
    date: `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}-01`,
    account: undefined as number | undefined,
    legacy_upgrade: undefined as number | undefined,
    uniqa_sds: undefined as number | undefined,
    uniqa_dds: undefined as number | undefined,
    finax: undefined as number | undefined,
    trading212: undefined as number | undefined,
});

function openCreate() {
    editingStatement.value = null;
    form.date = `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}-01`;
    form.account = undefined;
    form.legacy_upgrade = undefined;
    form.uniqa_sds = undefined;
    form.uniqa_dds = undefined;
    form.finax = undefined;
    form.trading212 = undefined;
    pickerYear.value = new Date().getFullYear();
    showDialog.value = true;
}

function openEdit(statement: Statement) {
    editingStatement.value = statement;
    const date = new Date(statement.date);
    form.date = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-01`;
    form.account = statement.account;
    form.legacy_upgrade = statement.legacy_upgrade;
    form.uniqa_sds = statement.uniqa_sds;
    form.uniqa_dds = statement.uniqa_dds;
    form.finax = statement.finax;
    form.trading212 = statement.trading212;
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
        ? update.url(editingStatement.value!.id)
        : store.url();

    const method = isEditing.value ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => {
            showDialog.value = false;
            editingStatement.value = null;
            form.reset();
        },
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
    return (
        statement.account +
        statement.legacy_upgrade +
        statement.uniqa_sds +
        statement.uniqa_dds +
        statement.finax +
        statement.trading212
    );
}

const sortedStatements = computed(() =>
    [...props.statements].sort(
        (stmtA, stmtB) =>
            new Date(stmtA.date).getTime() - new Date(stmtB.date).getTime(),
    ),
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
    const gains = Object.values(gainByStatementId.value).filter(
        (val): val is number => val !== null,
    );

    if (gains.length === 0) {
        return 0;
    }

    return Math.round(gains.reduce((sum, val) => sum + val, 0) / gains.length);
});

const averageGainPercent = computed(() => {
    const percents = Object.values(gainPercentByStatementId.value).filter(
        (val): val is number => val !== null,
    );

    if (percents.length === 0) {
        return 0;
    }

    return percents.reduce((sum, val) => sum + val, 0) / percents.length;
});

type ColumnKey = (typeof columns)[number]['key'];

const columnGainByStatementId = computed(() => {
    const result: Record<
        number,
        Record<ColumnKey, { gain: number; percent: number | null }>
    > = {};
    const sorted = sortedStatements.value;

    for (let idx = 0; idx < sorted.length; idx++) {
        const entry: Record<string, { gain: number; percent: number | null }> =
            {};

        for (const col of columns) {
            if (idx === 0) {
                entry[col.key] = { gain: 0, percent: null };
            } else {
                const prev = sorted[idx - 1][col.key];
                const curr = sorted[idx][col.key];
                entry[col.key] = {
                    gain: curr - prev,
                    percent: prev !== 0 ? ((curr - prev) / prev) * 100 : null,
                };
            }
        }

        result[sorted[idx].id] = entry as Record<
            ColumnKey,
            { gain: number; percent: number | null }
        >;
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

    return {
        labels,
        datasets: columns.map((col) => ({
            label: col.label,
            data: sorted.map((stmt) => stmt[col.key]),
            borderColor: col.color,
            backgroundColor: col.color
                .replace('rgb(', 'rgba(')
                .replace(')', ', 0.1)'),
            borderWidth: 3,
            pointRadius: 5,
            pointHoverRadius: 5,
            fill: true,
            tension: 0,
        })),
    };
});

const chartOptions = {
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
            },
        },
        tooltip: {
            reverse: true,
            callbacks: {
                title: (items: TooltipItem<'line'>[]) => {
                    const stmt = sortedStatements.value[items[0].dataIndex];
                    const date = new Date(stmt.date);

                    return date.toLocaleDateString('en-US', {
                        month: 'short',
                        year: 'numeric',
                    });
                },
                label: (context: TooltipItem<'line'>) => {
                    const value = context.parsed.y ?? 0;

                    return ` ${context.dataset.label}: ${value.toLocaleString('fr-FR')}`;
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
                callback: (value: number | string) =>
                    Number(value).toLocaleString('fr-FR'),
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
        title: 'Net Worth',
        href: index(),
    },
];
</script>

<template>
    <Head title="Net Worth" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-[4fr_6fr]">
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
                                    <th class="w-0 pl-4"></th>

                                    <th class="border-r px-4 py-2">Month</th>

                                    <th
                                        v-for="col in columns"
                                        :key="col.key"
                                        class="px-2 py-2 text-right"
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

                                                <DropdownMenuContent
                                                    align="start"
                                                >
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

                                        <td class="border-r px-4 py-3">
                                            <span class="text-muted-foreground">
                                                {{
                                                    formatMonth(statement.date)
                                                }}
                                            </span>
                                        </td>

                                        <td
                                            v-for="col in columns"
                                            :key="col.key"
                                            class="px-2 py-3 text-right font-mono"
                                            :class="
                                                columnGainByStatementId[
                                                    statement.id
                                                ][col.key].gain < 0
                                                    ? 'text-red-600 dark:text-red-400'
                                                    : ''
                                            "
                                        >
                                            <TooltipProvider
                                                v-if="
                                                    columnGainByStatementId[
                                                        statement.id
                                                    ][col.key].percent !== null
                                                "
                                            >
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <span
                                                            class="cursor-default"
                                                        >
                                                            {{
                                                                formatAmount(
                                                                    statement[
                                                                        col.key
                                                                    ],
                                                                )
                                                            }}
                                                        </span>
                                                    </TooltipTrigger>

                                                    <TooltipContent>
                                                        <span
                                                            :class="
                                                                columnGainByStatementId[
                                                                    statement.id
                                                                ][col.key]
                                                                    .gain >= 0
                                                                    ? 'text-green-400 dark:text-green-600'
                                                                    : 'text-red-400 dark:text-red-600'
                                                            "
                                                        >
                                                            {{
                                                                columnGainByStatementId[
                                                                    statement.id
                                                                ][col.key]
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
                                                                                .key
                                                                        ].gain,
                                                                    ),
                                                                )
                                                            }}
                                                            ({{
                                                                columnGainByStatementId[
                                                                    statement.id
                                                                ][col.key]
                                                                    .percent! >=
                                                                0
                                                                    ? '+'
                                                                    : '−'
                                                            }}{{
                                                                Math.abs(
                                                                    columnGainByStatementId[
                                                                        statement
                                                                            .id
                                                                    ][col.key]
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
                                                        statement[col.key],
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
                                                class="flex items-center gap-1"
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

                    <div
                        v-for="col in columns"
                        :key="col.key"
                        class="space-y-2"
                    >
                        <Label :for="`stmt-${col.key}`">{{ col.label }}</Label>

                        <Input
                            :id="`stmt-${col.key}`"
                            v-model="form[col.key]"
                            type="number"
                            min="0"
                            class="[-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                            :disabled="form.processing"
                        />

                        <InputError :message="form.errors[col.key]" />
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
