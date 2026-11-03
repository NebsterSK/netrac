<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import type { TooltipItem } from 'chart.js';
import {
    Chart as ChartJS,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip as ChartTooltip,
} from 'chart.js';
import { EllipsisVertical, Pencil, Plus, Trash2 } from 'lucide-vue-next';
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
import { useAppearance } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import { todayString } from '@/lib/buzerlistok';
import { dashboard } from '@/routes';
import { destroy, index, store, update } from '@/routes/health/weights';
import type { BreadcrumbItem } from '@/types';

type Weight = App.Data.Health.WeightData;

const props = defineProps<{
    /** Newest first. */
    weights: Weight[];
}>();

const DAY_MS = 86_400_000;

/** `YYYY-MM-DD` as whole days since the epoch, so the chart's x axis is linear in time. */
function dayNumber(date: string): number {
    const [year, month, day] = date.split('-').map(Number);

    return Date.UTC(year, month - 1, day) / DAY_MS;
}

/** European `d. m. yyyy` label for a day number. */
function formatDay(dayValue: number, withYear = true): string {
    const date = new Date(dayValue * DAY_MS);
    const label = `${date.getUTCDate()}. ${date.getUTCMonth() + 1}.`;

    return withYear ? `${label} ${date.getUTCFullYear()}` : label;
}

function formatDate(date: string): string {
    return formatDay(dayNumber(date));
}

function formatWeight(weight: number): string {
    return weight.toLocaleString('sk-SK', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    });
}

/** Change against the previous (older) entry; null for the oldest one. */
function changeAt(idx: number): number | null {
    const older = props.weights[idx + 1];

    return older === undefined
        ? null
        : Math.round((props.weights[idx].weight - older.weight) * 10) / 10;
}

function changeClass(change: number | null): string {
    if (change === null || change === 0) {
        return 'text-muted-foreground';
    }

    return change < 0
        ? 'text-green-600 dark:text-green-400'
        : 'text-red-600 dark:text-red-400';
}

function formatChange(change: number | null): string {
    if (change === null) {
        return '—';
    }

    return `${change > 0 ? '+' : ''}${formatWeight(change)}`;
}

// --- Form ------------------------------------------------------------------

const showDialog = ref(false);
const editingWeight = ref<Weight | null>(null);
const isEditing = computed(() => editingWeight.value !== null);

const form = useForm({
    date: todayString(),
    weight: null as number | null,
});

/** Mirror `v-model.number`: an empty input clears the weight. */
function setWeight(value: string | number) {
    form.weight = value === '' ? null : Number(value);
}

function openCreate() {
    editingWeight.value = null;
    form.reset();
    form.date = todayString();
    form.weight = props.weights[0]?.weight ?? null;
    form.clearErrors();
    showDialog.value = true;
}

function openEdit(weight: Weight) {
    editingWeight.value = weight;
    form.date = weight.date;
    form.weight = weight.weight;
    form.clearErrors();
    showDialog.value = true;
}

function submitForm() {
    const url = isEditing.value
        ? update.url(editingWeight.value!.id)
        : store.url();

    const method = isEditing.value ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            showDialog.value = false;
            editingWeight.value = null;
            form.reset();
        },
    });
}

function deleteWeight(weight: Weight) {
    if (!confirm(`Delete the weight entry from ${formatDate(weight.date)}?`)) {
        return;
    }

    router.delete(destroy.url(weight.id), { preserveScroll: true });
}

// --- Chart -----------------------------------------------------------------

ChartJS.register(LinearScale, LineElement, PointElement, ChartTooltip);

const LINE_COLOR = 'rgb(59, 130, 246)';

const chartData = computed(() => ({
    datasets: [
        {
            label: 'Weight',
            data: [...props.weights].reverse().map((weight) => ({
                x: dayNumber(weight.date),
                y: weight.weight,
            })),
            borderColor: LINE_COLOR,
            backgroundColor: LINE_COLOR,
            borderWidth: 3,
            pointRadius: 4,
            pointHoverRadius: 6,
            tension: 0.2,
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
                ticks: {
                    color: textColor,
                    maxRotation: 0,
                    callback: (value: string | number) =>
                        formatDay(Number(value), false),
                },
                grid: { color: gridColor },
            },
            y: {
                ticks: {
                    color: textColor,
                    callback: (value: string | number) => `${value} kg`,
                },
                grid: { color: gridColor },
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
        title: 'Weight',
        href: index(),
    },
];
</script>

<template>
    <Head title="Weight" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex flex-col gap-4 p-4 lg:flex-row lg:flex-wrap lg:items-start"
        >
            <Card class="min-w-0 lg:max-w-full lg:flex-none">
                <CardHeader>
                    <CardTitle class="leading-8">Table</CardTitle>

                    <CardAction>
                        <Button
                            size="icon-sm"
                            variant="outline"
                            aria-label="Add weight"
                            @click="openCreate()"
                        >
                            <Plus class="size-3" />
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent>
                    <table
                        class="w-full text-left text-sm"
                        aria-label="Weight entries"
                    >
                        <thead class="border-b text-xs text-muted-foreground">
                            <tr>
                                <th class="px-4 py-2 font-medium">Date</th>

                                <th class="px-4 py-2 text-right font-medium">
                                    Weight
                                </th>

                                <th class="px-4 py-2 text-right font-medium">
                                    Change
                                </th>

                                <th class="w-0 px-4 py-2"></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-if="weights.length === 0">
                                <td
                                    colspan="4"
                                    class="px-4 py-6 text-center text-muted-foreground"
                                >
                                    No entries yet.
                                </td>
                            </tr>

                            <tr
                                v-for="(weight, idx) in weights"
                                :key="weight.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/50"
                            >
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ formatDate(weight.date) }}
                                </td>

                                <td
                                    class="px-4 py-3 text-right font-mono font-medium whitespace-nowrap"
                                >
                                    {{ formatWeight(weight.weight) }} kg
                                </td>

                                <td
                                    class="px-4 py-3 text-right font-mono whitespace-nowrap"
                                    :class="changeClass(changeAt(idx))"
                                >
                                    {{ formatChange(changeAt(idx)) }}
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <button
                                                class="flex items-center"
                                                aria-label="Actions"
                                            >
                                                <EllipsisVertical
                                                    class="size-4 text-muted-foreground"
                                                />
                                            </button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem
                                                @click="openEdit(weight)"
                                            >
                                                <Pencil class="size-4" />
                                                Edit
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                class="text-red-600 dark:text-red-400"
                                                @click="deleteWeight(weight)"
                                            >
                                                <Trash2 class="size-4" />
                                                Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <Card
                class="h-80 lg:sticky lg:top-0 lg:h-[90vh] lg:min-w-md lg:flex-1"
            >
                <CardHeader>
                    <CardTitle>Chart</CardTitle>
                </CardHeader>

                <CardContent class="min-h-0 flex-1 overflow-hidden">
                    <div v-if="weights.length >= 2" class="h-full">
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
                    <DialogTitle>
                        {{ isEditing ? 'Edit Weight' : 'Add Weight' }}
                    </DialogTitle>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitForm">
                    <fieldset :disabled="form.processing" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="weight-date">Date</Label>

                            <Input
                                id="weight-date"
                                v-model="form.date"
                                type="date"
                                required
                            />

                            <InputError :message="form.errors.date" />
                        </div>

                        <div class="space-y-2">
                            <Label for="weight-value">Weight (kg)</Label>

                            <Input
                                id="weight-value"
                                :model-value="form.weight ?? undefined"
                                @update:model-value="setWeight"
                                type="number"
                                inputmode="decimal"
                                step="0.1"
                                min="1"
                                max="999.9"
                                required
                                autofocus
                            />

                            <InputError :message="form.errors.weight" />
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                @click="showDialog = false"
                            >
                                Cancel
                            </Button>

                            <Button type="submit">
                                <Spinner v-if="form.processing" />
                                {{ isEditing ? 'Update' : 'Add' }}
                            </Button>
                        </DialogFooter>
                    </fieldset>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
