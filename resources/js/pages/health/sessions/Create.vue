<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { GripVertical, Shuffle, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import sessionRoutes from '@/routes/health/sessions';
import type { BreadcrumbItem } from '@/types';

type ExerciseCategory = App.Data.Health.ExerciseCategoryData;

type Exercise = App.Data.Health.ExerciseData;

const props = defineProps<{
    exercises: Exercise[];
}>();

const form = useForm<{ exercise_ids: number[] }>({
    exercise_ids: [],
});

const exercisesById = computed(() => {
    const map = new Map<number, Exercise>();

    for (const exercise of props.exercises) {
        map.set(exercise.id, exercise);
    }

    return map;
});

const selectedExercises = computed(() =>
    form.exercise_ids
        .map((id) => exercisesById.value.get(id))
        .filter((exercise): exercise is Exercise => exercise !== undefined),
);

const exercisesByCategory = computed(() => {
    const groups = new Map<
        number,
        { category: ExerciseCategory; items: Exercise[] }
    >();

    for (const exercise of props.exercises) {
        const group = groups.get(exercise.exerciseCategory.id);

        if (group) {
            group.items.push(exercise);
        } else {
            groups.set(exercise.exerciseCategory.id, {
                category: exercise.exerciseCategory,
                items: [exercise],
            });
        }
    }

    return Array.from(groups.values()).sort((groupA, groupB) =>
        groupA.category.name.localeCompare(groupB.category.name),
    );
});

const availableExercises = computed(() =>
    props.exercises.filter(
        (exercise) => !form.exercise_ids.includes(exercise.id),
    ),
);

function addExercise(idValue: string) {
    const id = Number(idValue);

    if (Number.isFinite(id) && id > 0 && !form.exercise_ids.includes(id)) {
        form.exercise_ids = [...form.exercise_ids, id];
    }
}

function removeExercise(id: number) {
    form.exercise_ids = form.exercise_ids.filter((value) => value !== id);
}

function moveExercise(fromIndex: number, toIndex: number) {
    if (toIndex < 0 || toIndex >= form.exercise_ids.length) {
        return;
    }

    const next = [...form.exercise_ids];
    const [moved] = next.splice(fromIndex, 1);
    next.splice(toIndex, 0, moved);
    form.exercise_ids = next;
}

const draggingIndex = ref<number | null>(null);

function onDragStart(index: number, event: DragEvent) {
    draggingIndex.value = index;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(index));
    }
}

function onDragOver(index: number) {
    if (draggingIndex.value === null || draggingIndex.value === index) {
        return;
    }

    moveExercise(draggingIndex.value, index);
    draggingIndex.value = index;
}

function onDragEnd() {
    draggingIndex.value = null;
}

function randomize() {
    const target = 8;

    // The lowest-priority tier (highest priority number) holds a single
    // category — reserve one of its exercises for the final slot.
    const lastTierPriority = props.exercises.reduce(
        (lowest, exercise) =>
            Math.max(lowest, exercise.exerciseCategory.priority),
        0,
    );

    const lastTierExercises = props.exercises.filter(
        (exercise) => exercise.exerciseCategory.priority === lastTierPriority,
    );

    const closingExercise =
        lastTierExercises.length > 0
            ? lastTierExercises[
                  Math.floor(Math.random() * lastTierExercises.length)
              ]
            : null;

    const bodyTarget = closingExercise ? target - 1 : target;

    const shuffledCategories = exercisesByCategory.value
        .filter((group) => group.category.priority !== lastTierPriority)
        .sort(() => Math.random() - 0.5);

    const picked: number[] = [];

    for (const group of shuffledCategories) {
        if (picked.length >= bodyTarget) {
            break;
        }

        const random =
            group.items[Math.floor(Math.random() * group.items.length)];

        picked.push(random.id);
    }

    if (picked.length < bodyTarget) {
        const remaining = props.exercises
            .filter(
                (exercise) =>
                    exercise.exerciseCategory.priority !== lastTierPriority &&
                    !picked.includes(exercise.id),
            )
            .sort(() => Math.random() - 0.5)
            .slice(0, bodyTarget - picked.length)
            .map((exercise) => exercise.id);

        picked.push(...remaining);
    }

    const pool = picked.map((id) => ({
        id,
        categoryId: exercisesById.value.get(id)?.exerciseCategory.id ?? 0,
        priority: exercisesById.value.get(id)?.exerciseCategory.priority ?? 0,
        pattern: exercisesById.value.get(id)?.movement_pattern ?? null,
    }));

    // Priority ascending (lower number first); stable, so the random pick
    // order is preserved within a priority band.
    pool.sort((itemA, itemB) => itemA.priority - itemB.priority);

    const ordered: number[] = [];
    let lastCategoryId: number | null = null;
    let lastPattern: string | null = null;

    // Two non-null exercises clash only when they share the same movement
    // pattern; nulls (core/cardio) never clash on pattern.
    const samePattern = (pattern: string | null) =>
        pattern !== null && pattern === lastPattern;

    while (pool.length > 0) {
        // Prefer the lowest-priority exercise that differs on both pattern and
        // category; then pattern only; then category only; finally anything.
        // Pattern is ranked first because that is the adjacency we most want to
        // avoid (e.g. two pushes back to back).
        let index = pool.findIndex(
            (item) =>
                !samePattern(item.pattern) &&
                item.categoryId !== lastCategoryId,
        );

        if (index === -1) {
            index = pool.findIndex((item) => !samePattern(item.pattern));
        }

        if (index === -1) {
            index = pool.findIndex(
                (item) => item.categoryId !== lastCategoryId,
            );
        }

        if (index === -1) {
            index = 0;
        }

        const [next] = pool.splice(index, 1);
        ordered.push(next.id);
        lastCategoryId = next.categoryId;
        lastPattern = next.pattern;
    }

    if (closingExercise) {
        ordered.push(closingExercise.id);
    }

    form.exercise_ids = ordered;
}

function submitForm() {
    form.post(sessionRoutes.store.url());
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Sessions',
        href: sessionRoutes.index(),
    },
    {
        title: 'Create',
        href: sessionRoutes.create(),
    },
];
</script>

<template>
    <Head title="New Session" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <form
            @submit.prevent="submitForm"
            class="grid grid-cols-1 gap-4 p-4 lg:max-w-3xl"
        >
            <fieldset :disabled="form.processing" class="space-y-4">
                <Card>
                    <CardHeader class="flex items-center justify-between gap-2">
                        <CardTitle class="leading-8">New Session</CardTitle>

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="cursor-pointer"
                            :disabled="exercises.length === 0"
                            @click="randomize()"
                        >
                            <Shuffle class="size-4" />
                            Randomize
                        </Button>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Select
                                :model-value="''"
                                :disabled="availableExercises.length === 0"
                                @update:model-value="
                                    (value) => addExercise(value as string)
                                "
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue
                                        :placeholder="
                                            availableExercises.length === 0
                                                ? 'All exercises added'
                                                : 'Add an exercise'
                                        "
                                    />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectGroup
                                        v-for="group in exercisesByCategory"
                                        :key="group.category.id"
                                    >
                                        <SelectLabel>{{
                                            group.category.name
                                        }}</SelectLabel>

                                        <SelectItem
                                            v-for="exercise in group.items.filter(
                                                (item) =>
                                                    !form.exercise_ids.includes(
                                                        item.id,
                                                    ),
                                            )"
                                            :key="exercise.id"
                                            :value="String(exercise.id)"
                                        >
                                            {{ exercise.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>

                            <InputError :message="form.errors.exercise_ids" />
                        </div>

                        <p
                            v-if="selectedExercises.length === 0"
                            class="py-6 text-center text-sm text-muted-foreground"
                        >
                            No exercises selected. Use the picker above or click
                            Randomize.
                        </p>

                        <ul v-else class="space-y-2">
                            <li
                                v-for="(exercise, index) in selectedExercises"
                                :key="exercise.id"
                                draggable="true"
                                class="flex items-center gap-3 rounded-md border bg-muted/30 px-3 py-2 transition-opacity"
                                :class="
                                    draggingIndex === index ? 'opacity-50' : ''
                                "
                                @dragstart="onDragStart(index, $event)"
                                @dragover.prevent="onDragOver(index)"
                                @dragend="onDragEnd()"
                                @drop.prevent="onDragEnd()"
                            >
                                <div
                                    class="cursor-grab text-muted-foreground active:cursor-grabbing"
                                    aria-label="Drag to reorder"
                                >
                                    <GripVertical class="size-4" />
                                </div>

                                <div class="flex flex-1 flex-col">
                                    <span class="font-medium">{{
                                        exercise.name
                                    }}</span>

                                    <Badge
                                        variant="secondary"
                                        class="mt-1 w-fit text-xs"
                                    >
                                        {{ exercise.exerciseCategory.name }}
                                    </Badge>
                                </div>

                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    class="cursor-pointer text-red-600 dark:text-red-400"
                                    aria-label="Remove exercise"
                                    @click="removeExercise(exercise.id)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <div class="flex justify-end">
                    <Button
                        type="submit"
                        class="cursor-pointer"
                        :disabled="selectedExercises.length === 0"
                    >
                        <Spinner v-if="form.processing" />
                        Save session
                    </Button>
                </div>
            </fieldset>
        </form>
    </AppLayout>
</template>
