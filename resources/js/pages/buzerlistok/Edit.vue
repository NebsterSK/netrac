<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { GripVertical, Plus, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import buzerlistok from '@/routes/buzerlistok';
import type { BreadcrumbItem } from '@/types';

type Week = App.Data.Buzerlistok.WeekData;

const props = defineProps<{
    week: Week;
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
    {
        title: 'Edit',
        href: buzerlistok.edit(props.week.id),
    },
];

// `key` is a client-only identifier so new goals (without an id) render stably.
type GoalField = { key: number; id: number | null; name: string };

let nextGoalKey = 1;

function makeGoalField(id: number | null = null, name = ''): GoalField {
    return { key: nextGoalKey++, id, name };
}

const form = useForm<{ starts_on: string; goals: GoalField[] }>({
    starts_on: props.week.starts_on,
    goals: props.week.goals.map((goal) => makeGoalField(goal.id, goal.name)),
});

const removedGoalsWithMarks = computed(() => {
    const keptIds = new Set(form.goals.map((goal) => goal.id));

    return props.week.goals.filter(
        (goal) => !keptIds.has(goal.id) && goal.marks.length > 0,
    );
});

function addGoalField() {
    form.goals = [...form.goals, makeGoalField()];
}

function removeGoalField(index: number) {
    if (form.goals.length === 1) {
        return;
    }

    form.goals = form.goals.filter((_, position) => position !== index);
}

function moveGoal(fromIndex: number, toIndex: number) {
    if (toIndex < 0 || toIndex >= form.goals.length) {
        return;
    }

    const next = [...form.goals];
    const [moved] = next.splice(fromIndex, 1);
    next.splice(toIndex, 0, moved);
    form.goals = next;
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

    moveGoal(draggingIndex.value, index);
    draggingIndex.value = index;
}

function onDragEnd() {
    draggingIndex.value = null;
}

function submitForm() {
    if (
        removedGoalsWithMarks.value.length > 0 &&
        !confirm(
            `Removing ${removedGoalsWithMarks.value.map((goal) => goal.name).join(', ')} also deletes their marks. Continue?`,
        )
    ) {
        return;
    }

    form.transform((data) => ({
        ...data,
        goals: data.goals
            .map((goal) => ({ id: goal.id, name: goal.name.trim() }))
            .filter((goal) => goal.name !== ''),
    })).put(buzerlistok.update.url(props.week.id));
}
</script>

<template>
    <Head title="Edit Buzerlistok" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <form
            @submit.prevent="submitForm"
            class="grid grid-cols-1 gap-4 p-4 lg:max-w-3xl"
        >
            <fieldset :disabled="form.processing" class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="leading-8">Edit week</CardTitle>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="starts_on">Week start</Label>

                            <Input
                                id="starts_on"
                                v-model="form.starts_on"
                                type="date"
                            />

                            <p class="text-xs text-muted-foreground">
                                Snaps to the Monday of that week. Existing marks
                                move along with it.
                            </p>

                            <InputError :message="form.errors.starts_on" />
                        </div>

                        <div class="space-y-2">
                            <Label>Goals</Label>

                            <ul class="space-y-2">
                                <li
                                    v-for="(goal, index) in form.goals"
                                    :key="goal.key"
                                    draggable="true"
                                    class="flex items-center gap-2 transition-opacity"
                                    :class="
                                        draggingIndex === index
                                            ? 'opacity-50'
                                            : ''
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

                                    <Input
                                        v-model="goal.name"
                                        placeholder="e.g. Exercise, Read, No sugar"
                                    />

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        class="cursor-pointer text-muted-foreground"
                                        :disabled="form.goals.length === 1"
                                        aria-label="Remove goal"
                                        @click="removeGoalField(index)"
                                    >
                                        <X class="size-4" />
                                    </Button>
                                </li>
                            </ul>

                            <InputError :message="form.errors.goals" />

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
                    </CardContent>
                </Card>

                <div class="flex justify-end">
                    <Button type="submit" class="cursor-pointer">
                        <Spinner v-if="form.processing" />
                        Save changes
                    </Button>
                </div>
            </fieldset>
        </form>
    </AppLayout>
</template>
