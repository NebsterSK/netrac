<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    Eraser,
    Minus,
    MessageSquareText,
    Pencil,
    Plus,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
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
import { Textarea } from '@/components/ui/textarea';
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
        brush: 'na',
        label: 'Does not apply (comment)',
        swatch: 'bg-neutral-700 dark:bg-neutral-400 border border-neutral-500 dark:border-neutral-300',
        cell: 'bg-neutral-700 dark:bg-neutral-400 border border-neutral-500 dark:border-neutral-300',
        glyph: MessageSquareText,
        glyphClass: 'text-white dark:text-neutral-900',
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

// Dates run newest-first down each table.
function daysDescending(week: Week): string[] {
    return [...week.days].reverse();
}

const submitting = ref(false);

function submitMark(
    goalId: number,
    date: string,
    status: MarkStatus | null,
    comment: string | null,
    onSuccess?: () => void,
) {
    if (submitting.value) {
        return;
    }

    submitting.value = true;

    router.put(
        marks.update.url(goalId),
        { marked_on: date, status, comment },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess,
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}

// --- Comment dialog (black / N/A) ----------------------------------------

const commentState = reactive<{
    open: boolean;
    goalId: number | null;
    date: string | null;
    text: string;
}>({
    open: false,
    goalId: null,
    date: null,
    text: '',
});

function openCommentDialog(goalId: number, date: string) {
    const existing = markFor(goalId, date);

    commentState.goalId = goalId;
    commentState.date = date;
    commentState.text = existing?.comment ?? '';
    commentState.open = true;
}

function saveComment() {
    if (
        commentState.goalId === null ||
        commentState.date === null ||
        commentState.text.trim() === ''
    ) {
        return;
    }

    submitMark(
        commentState.goalId,
        commentState.date,
        'na',
        commentState.text.trim(),
        () => {
            commentState.open = false;
        },
    );
}

function paintCell(goalId: number, date: string) {
    if (submitting.value) {
        return;
    }

    if (activeBrush.value === 'erase') {
        submitMark(goalId, date, null, null);

        return;
    }

    if (activeBrush.value === 'na') {
        openCommentDialog(goalId, date);

        return;
    }

    submitMark(goalId, date, activeBrush.value, null);
}

// --- Create week dialog ---------------------------------------------------

function currentMonday(): string {
    const now = new Date();
    const day = now.getDay(); // 0 = Sunday
    const diff = day === 0 ? -6 : 1 - day;
    const monday = new Date(
        now.getFullYear(),
        now.getMonth(),
        now.getDate() + diff,
    );

    const year = monday.getFullYear();
    const month = String(monday.getMonth() + 1).padStart(2, '0');
    const date = String(monday.getDate()).padStart(2, '0');

    return `${year}-${month}-${date}`;
}

const createOpen = ref(false);

type GoalField = { id: number; name: string };

let nextGoalFieldId = 1;

function makeGoalField(): GoalField {
    return { id: nextGoalFieldId++, name: '' };
}

const createForm = useForm<{ starts_on: string; goals: GoalField[] }>({
    starts_on: currentMonday(),
    goals: [makeGoalField()],
});

function openCreateDialog() {
    createForm.clearErrors();
    createForm.starts_on = currentMonday();
    createForm.goals = [makeGoalField()];
    createOpen.value = true;
}

function addGoalField() {
    createForm.goals = [...createForm.goals, makeGoalField()];
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
                <span class="text-sm font-medium text-muted-foreground">
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
            <Card v-for="week in weeks" :key="week.id" class="self-start">
                <CardHeader class="flex items-center justify-between gap-2">
                    <CardTitle class="leading-8">
                        {{ weekRange(week) }}
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

                <CardContent class="overflow-x-auto">
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
                                    class="px-2 py-1 text-left font-medium"
                                ></th>

                                <th
                                    v-for="goal in week.goals"
                                    :key="goal.id"
                                    class="max-w-32 px-2 py-1 text-left align-bottom font-medium"
                                >
                                    <span
                                        class="block truncate"
                                        :title="goal.name"
                                    >
                                        {{ goal.name }}
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
                                    class="px-2 py-1 whitespace-nowrap text-muted-foreground"
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
                                        :title="
                                            markFor(goal.id, date)?.comment ??
                                            undefined
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
                                track. The date snaps to the Monday of that
                                week.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="space-y-2">
                            <Label for="starts_on">Week start</Label>

                            <Input
                                id="starts_on"
                                v-model="createForm.starts_on"
                                type="date"
                            />

                            <InputError
                                :message="createForm.errors.starts_on"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label>Goals</Label>

                            <div
                                v-for="(goal, index) in createForm.goals"
                                :key="goal.id"
                                class="flex items-center gap-2"
                            >
                                <Input
                                    v-model="goal.name"
                                    placeholder="e.g. Exercise, Read, No sugar"
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
                            <Button type="submit" class="cursor-pointer">
                                <Spinner v-if="createForm.processing" />
                                Create
                            </Button>
                        </DialogFooter>
                    </fieldset>
                </form>
            </DialogContent>
        </Dialog>

        <!-- N/A comment dialog -->
        <Dialog v-model:open="commentState.open">
            <DialogContent>
                <form @submit.prevent="saveComment">
                    <fieldset :disabled="submitting" class="space-y-4">
                        <DialogHeader>
                            <DialogTitle>Why doesn't it apply?</DialogTitle>

                            <DialogDescription>
                                Add a short note explaining why this goal didn't
                                apply that day.
                            </DialogDescription>
                        </DialogHeader>

                        <Textarea
                            v-model="commentState.text"
                            rows="3"
                            placeholder="e.g. Rest day, was travelling…"
                            autofocus
                        />

                        <DialogFooter>
                            <Button
                                type="submit"
                                class="cursor-pointer"
                                :disabled="commentState.text.trim() === ''"
                            >
                                <Spinner v-if="submitting" />
                                Save
                            </Button>
                        </DialogFooter>
                    </fieldset>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
