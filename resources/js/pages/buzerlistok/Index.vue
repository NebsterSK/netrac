<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useIntervalFn } from '@vueuse/core';
import {
    Check,
    Copy,
    Eraser,
    Minus,
    Pencil,
    Plus,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import WeekStartPicker from '@/components/WeekStartPicker.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import buzerlistok from '@/routes/buzerlistok';
import marks from '@/routes/buzerlistok/goals/marks';
import type { BreadcrumbItem } from '@/types';

type Week = App.Data.Buzerlistok.WeekData;

type Mark = App.Data.Buzerlistok.MarkData;

type MarkStatus = App.Enums.Buzerlistok.MarkStatus;

type Brush = MarkStatus | 'erase';

const props = defineProps<{
    weeks: Week[];
    takenWeekStarts: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Buzerlistok',
        href: buzerlistok.index(),
    },
];

// --- Status palette -------------------------------------------------------

type PaletteEntry = {
    brush: Brush;
    label: string;
    swatch: string;
    cell: string;
    glyph: typeof Check;
    glyphClass: string;
};

const PALETTE: PaletteEntry[] = [
    {
        brush: 'success',
        label: 'Success',
        swatch: 'bg-green-500 dark:bg-green-600',
        cell: 'bg-green-500 dark:bg-green-600',
        glyph: Check,
        glyphClass: 'text-white',
    },
    {
        brush: 'partial',
        label: 'Partial success',
        swatch: 'bg-blue-500 dark:bg-blue-600',
        cell: 'bg-blue-500 dark:bg-blue-600',
        glyph: Minus,
        glyphClass: 'text-white',
    },
    {
        brush: 'fail',
        label: 'Fail',
        swatch: 'bg-red-500 dark:bg-red-600',
        cell: 'bg-red-500 dark:bg-red-600',
        glyph: X,
        glyphClass: 'text-white',
    },
];

const activeBrush = ref<Brush>('success');

const cellClassByStatus = computed(() => {
    const map = new Map<MarkStatus, string>();

    for (const entry of PALETTE) {
        if (entry.brush !== 'erase') {
            map.set(entry.brush, entry.cell);
        }
    }

    return map;
});

const paletteByStatus = computed(() => {
    const map = new Map<MarkStatus, PaletteEntry>();

    for (const entry of PALETTE) {
        if (entry.brush !== 'erase') {
            map.set(entry.brush, entry);
        }
    }

    return map;
});

// --- Marks lookup ---------------------------------------------------------

function cellKey(goalId: number, date: string): string {
    return `${goalId}|${date}`;
}

const markIndex = computed(() => {
    const map = new Map<string, Mark>();

    for (const week of props.weeks) {
        for (const goal of week.goals) {
            for (const mark of goal.marks) {
                map.set(cellKey(goal.id, mark.marked_on), mark);
            }
        }
    }

    return map;
});

function markFor(goalId: number, date: string): Mark | undefined {
    return markIndex.value.get(cellKey(goalId, date));
}

// --- Day status -----------------------------------------------------------

type DayStatus = 'success' | 'fail';

function todayString(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const date = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${date}`;
}

const today = ref(todayString());

useIntervalFn(() => {
    today.value = todayString();
}, 60_000);

/**
 * Derived, never stored: a single failed goal fails the day; otherwise a day
 * with every goal marked succeeds. Past days must resolve, so an unmarked goal
 * counts as failed. Today stays open until it is fully marked, and future
 * days have no status.
 */
function dayStatus(week: Week, date: string): DayStatus | null {
    if (week.goals.length === 0 || date > today.value) {
        return null;
    }

    const statuses = week.goals.map((goal) => markFor(goal.id, date)?.status);

    if (statuses.includes('fail')) {
        return 'fail';
    }

    if (statuses.every((status) => status !== undefined)) {
        return 'success';
    }

    return date < today.value ? 'fail' : null;
}

/**
 * Share of goal-days that succeeded (success or partial), rounded to a whole
 * percent. Past days count every goal, so an unmarked one counts as missed;
 * today counts only what is already marked; future days are left out. Null
 * when nothing counts yet.
 */
function weekSuccessRate(week: Week): number | null {
    let counted = 0;
    let succeeded = 0;

    for (const date of week.days) {
        if (date > today.value) {
            continue;
        }

        for (const goal of week.goals) {
            const status = markFor(goal.id, date)?.status;

            if (date === today.value && status === undefined) {
                continue;
            }

            counted++;

            if (status === 'success' || status === 'partial') {
                succeeded++;
            }
        }
    }

    return counted === 0 ? null : Math.round((succeeded / counted) * 100);
}

/** Red at 0 %, through yellow, to green at 100 %. */
function successRateColor(rate: number): string {
    return `hsl(${Math.round(rate * 1.2)} 75% 50%)`;
}

// Dates run newest-first down each table.
function daysDescending(week: Week): string[] {
    return [...week.days].reverse();
}

const submitting = ref(false);

function submitMark(
    goalId: number,
    date: string,
    status: MarkStatus | null,
) {
    if (submitting.value) {
        return;
    }

    submitting.value = true;

    router.put(
        marks.update.url(goalId),
        { marked_on: date, status },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}

function paintCell(goalId: number, date: string) {
    if (submitting.value) {
        return;
    }

    if (activeBrush.value === 'erase') {
        submitMark(goalId, date, null);

        return;
    }

    submitMark(goalId, date, activeBrush.value);
}

// --- Create week dialog ---------------------------------------------------

function toDateString(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

/** This week's Monday, or the first later Monday that has no week yet. */
function firstFreeMonday(): string {
    const now = new Date();
    const day = now.getDay(); // 0 = Sunday
    const diff = day === 0 ? -6 : 1 - day;
    const monday = new Date(
        now.getFullYear(),
        now.getMonth(),
        now.getDate() + diff,
    );

    while (props.takenWeekStarts.includes(toDateString(monday))) {
        monday.setDate(monday.getDate() + 7);
    }

    return toDateString(monday);
}

const createOpen = ref(false);

type GoalField = { id: number; name: string };

let nextGoalFieldId = 1;

function makeGoalField(): GoalField {
    return { id: nextGoalFieldId++, name: '' };
}

const createForm = useForm<{ starts_on: string; goals: GoalField[] }>({
    starts_on: firstFreeMonday(),
    goals: [makeGoalField()],
});

const createStartsOnTaken = computed(() =>
    props.takenWeekStarts.includes(createForm.starts_on),
);

function openCreateDialog() {
    createForm.clearErrors();
    createForm.starts_on = firstFreeMonday();
    createForm.goals = [makeGoalField()];
    createOpen.value = true;
}

const goalFields = ref<HTMLElement | null>(null);

/**
 * Insert an empty goal (at the end by default) and focus it so it can be typed
 * into straight away.
 */
async function addGoalField(position = createForm.goals.length) {
    const goals = [...createForm.goals];
    goals.splice(position, 0, makeGoalField());
    createForm.goals = goals;

    await nextTick();

    goalFields.value
        ?.querySelectorAll<HTMLInputElement>('[data-goal-input]')
        [position]?.focus();
}

/** Enter adds a goal below the current one instead of submitting the form. */
function onGoalEnter(event: KeyboardEvent, index: number) {
    if (event.isComposing) {
        return;
    }

    event.preventDefault();
    addGoalField(index + 1);
}

function removeGoalField(index: number) {
    if (createForm.goals.length === 1) {
        return;
    }

    createForm.goals = createForm.goals.filter(
        (_, position) => position !== index,
    );
}

function submitCreate() {
    createForm
        .transform((data) => ({
            ...data,
            goals: data.goals
                .map((goal) => goal.name.trim())
                .filter((name) => name !== ''),
        }))
        .post(buzerlistok.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                createOpen.value = false;
                createForm.reset();
            },
        });
}

// Weeks arrive newest first; only the newest can be carried forward.
const latestWeekId = computed(() => props.weeks[0]?.id ?? null);

const duplicatingWeekId = ref<number | null>(null);

function duplicateWeek(week: Week) {
    router.post(
        buzerlistok.duplicate.url(week.id),
        {},
        {
            preserveScroll: true,
            onStart: () => {
                duplicatingWeekId.value = week.id;
            },
            onFinish: () => {
                duplicatingWeekId.value = null;
            },
        },
    );
}

function deleteWeek(week: Week) {
    if (!confirm('Delete this buzerlistok and all its marks?')) {
        return;
    }

    router.delete(buzerlistok.destroy.url(week.id), { preserveScroll: true });
}

// --- Formatting -----------------------------------------------------------

function toLocalDate(dateString: string): Date {
    const [year, month, day] = dateString.split('-').map(Number);

    return new Date(year, month - 1, day);
}

function formatDay(dateString: string): string {
    return toLocalDate(dateString).toLocaleDateString('en-GB', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
    });
}

function weekRange(week: Week): string {
    const start = toLocalDate(week.days[0]);
    const end = toLocalDate(week.days[week.days.length - 1]);

    const startLabel = start.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
    });
    const endLabel = end.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });

    return `${startLabel} – ${endLabel}`;
}
</script>

<template>
    <Head title="Buzerlistok" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">
            <!-- Palette toolbar -->
            <div
                class="sticky top-0 z-10 flex flex-wrap items-center gap-3 rounded-xl border bg-background/95 p-3 backdrop-blur"
            >
                <span
                    class="text-sm font-medium text-muted-foreground max-sm:hidden"
                >
                    Brush:
                </span>

                <div class="flex items-center gap-2">
                    <button
                        v-for="entry in PALETTE"
                        :key="entry.brush"
                        type="button"
                        :title="entry.label"
                        :aria-label="entry.label"
                        class="flex size-8 cursor-pointer items-center justify-center rounded-md ring-offset-2 ring-offset-background transition-all"
                        :class="[
                            entry.swatch,
                            activeBrush === entry.brush
                                ? 'ring-2 ring-primary'
                                : 'opacity-70 hover:opacity-100',
                        ]"
                        @click="activeBrush = entry.brush"
                    >
                        <component
                            :is="entry.glyph"
                            class="size-4"
                            :class="entry.glyphClass"
                        />
                    </button>

                    <button
                        type="button"
                        title="Erase"
                        aria-label="Erase"
                        class="flex size-8 cursor-pointer items-center justify-center rounded-md border ring-offset-2 ring-offset-background transition-all"
                        :class="
                            activeBrush === 'erase'
                                ? 'ring-2 ring-primary'
                                : 'opacity-70 hover:opacity-100'
                        "
                        @click="activeBrush = 'erase'"
                    >
                        <Eraser class="size-4" />
                    </button>
                </div>

                <div class="ml-auto">
                    <Button
                        size="sm"
                        class="cursor-pointer"
                        @click="openCreateDialog()"
                    >
                        <Plus class="size-4" />
                        New week
                    </Button>
                </div>
            </div>

            <p
                v-if="weeks.length === 0"
                class="py-12 text-center text-sm text-muted-foreground"
            >
                No weeks yet. Create one to start tracking your goals.
            </p>

            <!-- One table per week, newest first -->
            <Card
                v-for="week in weeks"
                :key="week.id"
                class="max-w-full self-start max-sm:self-stretch"
            >
                <CardHeader class="flex items-center justify-between gap-2">
                    <CardTitle
                        class="flex flex-wrap items-baseline gap-x-3 leading-8"
                    >
                        {{ weekRange(week) }}

                        <span
                            v-if="weekSuccessRate(week) !== null"
                            class="font-mono text-base font-semibold"
                            :style="{
                                color: successRateColor(weekSuccessRate(week)!),
                            }"
                            :title="`${weekSuccessRate(week)}% of goals succeeded`"
                        >
                            {{ weekSuccessRate(week) }}%
                        </span>
                    </CardTitle>

                    <div class="flex items-center gap-1">
                        <Button
                            as-child
                            variant="ghost"
                            size="icon-sm"
                            class="cursor-pointer"
                        >
                            <Link
                                :href="buzerlistok.edit(week.id)"
                                aria-label="Edit week"
                            >
                                <Pencil class="size-4" />
                            </Link>
                        </Button>

                        <Button
                            v-if="week.id === latestWeekId"
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            class="cursor-pointer"
                            aria-label="Duplicate week into the following week"
                            title="Duplicate into next week"
                            :disabled="duplicatingWeekId !== null"
                            @click="duplicateWeek(week)"
                        >
                            <Spinner
                                v-if="duplicatingWeekId === week.id"
                                class="size-4"
                            />

                            <Copy v-else class="size-4" />
                        </Button>

                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            class="cursor-pointer text-red-600 dark:text-red-400"
                            aria-label="Delete week"
                            @click="deleteWeek(week)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </CardHeader>

                <CardContent class="overflow-x-auto max-sm:px-4">
                    <p
                        v-if="week.goals.length === 0"
                        class="py-4 text-center text-sm text-muted-foreground"
                    >
                        No goals for this week.
                    </p>

                    <table
                        v-else
                        class="border-separate border-spacing-1 text-sm"
                    >
                        <thead>
                            <tr>
                                <th
                                    class="px-2 py-1 text-left font-medium max-sm:sticky max-sm:pl-0 max-sm:left-0 max-sm:z-10 max-sm:bg-card"
                                ></th>

                                <th
                                    v-for="goal in week.goals"
                                    :key="goal.id"
                                    class="max-w-32 px-2 py-1 text-left align-bottom font-medium max-sm:max-w-9 max-sm:px-0"
                                >
                                    <span
                                        class="block truncate max-sm:mx-auto max-sm:max-h-28 max-sm:rotate-180 max-sm:[writing-mode:vertical-rl]"
                                        :title="goal.name"
                                    >
                                        {{ goal.name }}
                                    </span>
                                </th>

                                <th
                                    class="px-2 py-1 pl-3 text-left align-bottom font-medium max-sm:max-w-9 max-sm:px-0 max-sm:pl-1"
                                >
                                    <span
                                        class="block max-sm:mx-auto max-sm:rotate-180 max-sm:[writing-mode:vertical-rl]"
                                    >
                                        Day
                                    </span>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="date in daysDescending(week)"
                                :key="date"
                            >
                                <td
                                    class="px-2 py-1 whitespace-nowrap text-muted-foreground max-sm:sticky max-sm:pl-0 max-sm:left-0 max-sm:z-10 max-sm:bg-card"
                                >
                                    {{ formatDay(date) }}
                                </td>

                                <td
                                    v-for="goal in week.goals"
                                    :key="goal.id"
                                    class="p-0"
                                >
                                    <button
                                        type="button"
                                        class="flex size-8 cursor-pointer items-center justify-center rounded-md border border-border/60 transition-colors"
                                        :class="
                                            markFor(goal.id, date)
                                                ? cellClassByStatus.get(
                                                      markFor(goal.id, date)!
                                                          .status,
                                                  )
                                                : 'bg-transparent hover:bg-muted'
                                        "
                                        :aria-label="`${goal.name} — ${formatDay(date)}${
                                            markFor(goal.id, date)
                                                ? ` — ${paletteByStatus.get(markFor(goal.id, date)!.status)?.label}`
                                                : ''
                                        }`"
                                        @click="paintCell(goal.id, date)"
                                    >
                                        <component
                                            :is="
                                                paletteByStatus.get(
                                                    markFor(goal.id, date)!
                                                        .status,
                                                )?.glyph
                                            "
                                            v-if="markFor(goal.id, date)"
                                            class="size-4"
                                            :class="
                                                paletteByStatus.get(
                                                    markFor(goal.id, date)!
                                                        .status,
                                                )?.glyphClass
                                            "
                                        />
                                    </button>
                                </td>

                                <td class="p-0 pl-3 max-sm:pl-1">
                                    <div
                                        class="flex size-8 items-center justify-center rounded-md border"
                                        role="img"
                                        :class="
                                            dayStatus(week, date) === null
                                                ? 'border-dashed border-border/60'
                                                : 'border-transparent ' +
                                                  cellClassByStatus.get(
                                                      dayStatus(week, date)!,
                                                  )
                                        "
                                        :aria-label="`${formatDay(date)} — ${
                                            dayStatus(week, date) === null
                                                ? 'Open'
                                                : dayStatus(week, date) ===
                                                    'success'
                                                  ? 'Day succeeded'
                                                  : 'Day failed'
                                        }`"
                                    >
                                        <Check
                                            v-if="
                                                dayStatus(week, date) ===
                                                'success'
                                            "
                                            class="size-5 text-white"
                                            stroke-width="3"
                                        />

                                        <X
                                            v-else-if="
                                                dayStatus(week, date) === 'fail'
                                            "
                                            class="size-5 text-white"
                                            stroke-width="3"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </div>

        <!-- Create week dialog -->
        <Dialog v-model:open="createOpen">
            <DialogContent>
                <form @submit.prevent="submitCreate">
                    <fieldset
                        :disabled="createForm.processing"
                        class="space-y-4"
                    >
                        <DialogHeader>
                            <DialogTitle>New week</DialogTitle>

                            <DialogDescription>
                                Pick the week and list the goals you want to
                                track.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="space-y-2">
                            <Label for="starts_on">Week start</Label>

                            <WeekStartPicker
                                id="starts_on"
                                v-model="createForm.starts_on"
                                :taken-week-starts="takenWeekStarts"
                            />

                            <InputError
                                :message="
                                    createForm.errors.starts_on ??
                                    (createStartsOnTaken
                                        ? 'A buzerlistok already exists for that week.'
                                        : undefined)
                                "
                            />
                        </div>

                        <div ref="goalFields" class="space-y-2">
                            <Label>Goals</Label>

                            <div
                                v-for="(goal, index) in createForm.goals"
                                :key="goal.id"
                                class="flex items-center gap-2"
                            >
                                <Input
                                    v-model="goal.name"
                                    placeholder="e.g. Exercise, Read, No sugar"
                                    data-goal-input
                                    @keydown.enter="onGoalEnter($event, index)"
                                />

                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    class="cursor-pointer text-muted-foreground"
                                    :disabled="createForm.goals.length === 1"
                                    aria-label="Remove goal"
                                    @click="removeGoalField(index)"
                                >
                                    <X class="size-4" />
                                </Button>
                            </div>

                            <InputError :message="createForm.errors.goals" />

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="cursor-pointer"
                                @click="addGoalField()"
                            >
                                <Plus class="size-4" />
                                Add goal
                            </Button>
                        </div>

                        <DialogFooter>
                            <Button
                                type="submit"
                                class="cursor-pointer"
                                :disabled="createStartsOnTaken"
                            >
                                <Spinner v-if="createForm.processing" />
                                Create
                            </Button>
                        </DialogFooter>
                    </fieldset>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
