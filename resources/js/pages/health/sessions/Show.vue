<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import sessionRoutes from '@/routes/health/sessions';
import sessionExerciseRoutes from '@/routes/health/sessions/exercises';
import type { BreadcrumbItem } from '@/types';

type SessionData = App.Data.Health.SessionDetailData;

const props = defineProps<{
    session: SessionData;
}>();

const completionState = reactive<Record<number, boolean>>(
    Object.fromEntries(
        props.session.exercises.map((exercise) => [
            exercise.id,
            exercise.completed,
        ]),
    ),
);

function toggleExercise(exerciseId: number, completed: boolean) {
    completionState[exerciseId] = completed;

    router.patch(
        sessionExerciseRoutes.update.url({
            session: props.session.id,
            exercise: exerciseId,
        }),
        { completed },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('en-US', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
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
        title: formatDate(props.session.performed_at),
        href: sessionRoutes.show(props.session.id),
    },
];
</script>

<template>
    <Head :title="`Session — ${formatDate(session.performed_at)}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-1 gap-4 p-4 lg:max-w-2xl">
            <Card>
                <CardHeader>
                    <CardTitle class="leading-8">
                        {{ formatDate(session.performed_at) }}
                    </CardTitle>
                </CardHeader>

                <CardContent>
                    <ul class="space-y-1">
                        <li
                            v-for="exercise in session.exercises"
                            :key="exercise.id"
                            class="flex items-center gap-3 rounded-md px-2 py-2 transition-colors hover:bg-muted/50"
                        >
                            <Checkbox
                                :id="`exercise-${exercise.id}`"
                                :model-value="completionState[exercise.id]"
                                @update:model-value="
                                    (value) =>
                                        toggleExercise(
                                            exercise.id,
                                            value === true,
                                        )
                                "
                            />

                            <Label
                                :for="`exercise-${exercise.id}`"
                                class="flex flex-1 cursor-pointer items-center justify-between gap-3"
                            >
                                <span
                                    :class="
                                        completionState[exercise.id]
                                            ? 'text-muted-foreground line-through'
                                            : ''
                                    "
                                >
                                    {{ exercise.name }}
                                </span>

                                <Badge variant="secondary" class="text-xs">
                                    {{ exercise.exerciseCategory.name }}
                                </Badge>
                            </Label>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
