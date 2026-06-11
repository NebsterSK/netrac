<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronsUpDown,
    ChevronUp,
    EllipsisVertical,
    Pencil,
    Plus,
    Search,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { destroy, index, store, update } from '@/routes/health/exercises';
import type { BreadcrumbItem } from '@/types';

type ExerciseCategory = App.Data.Health.ExerciseCategoryData;
type Exercise = App.Data.Health.ExerciseData;

const movementPatternOptions: { value: string; label: string }[] = [
    { value: 'none', label: 'None' },
    { value: 'Push', label: 'Push' },
    { value: 'Pull', label: 'Pull' },
    { value: 'Squat', label: 'Squat' },
    { value: 'Hinge', label: 'Hinge' },
];

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

const props = defineProps<{
    exercises: Exercise[];
    meta: PaginationMeta;
    categories: ExerciseCategory[];
    filters: {
        name: string;
        exercise_category_ids: string[];
    };
    sort: string;
}>();

const search = ref(props.filters.name ?? '');
const selectedCategories = ref<string[]>(
    props.filters.exercise_category_ids ?? [],
);
const sort = ref(props.sort);
const loading = ref(false);

const hasActiveFilters = computed(
    () =>
        search.value !== '' ||
        selectedCategories.value.length > 0 ||
        sort.value !== 'name',
);

function applyFilters(page?: number) {
    const filter: Record<string, string> = {};

    if (search.value) {
        filter.name = search.value;
    }

    if (selectedCategories.value.length > 0) {
        filter.exercise_category_id = selectedCategories.value.join(',');
    }

    const query: {
        filter?: Record<string, string>;
        sort?: string;
        page?: number;
    } = {};

    if (Object.keys(filter).length > 0) {
        query.filter = filter;
    }

    if (sort.value !== 'name') {
        query.sort = sort.value;
    }

    if (page && page > 1) {
        query.page = page;
    }

    router.get(index.url(), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => {
            loading.value = true;
        },
        onFinish: () => {
            loading.value = false;
        },
    });
}

const debouncedApply = useDebounceFn(() => applyFilters(), 300);

function toggleCategory(id: string) {
    selectedCategories.value = selectedCategories.value.includes(id)
        ? selectedCategories.value.filter((value) => value !== id)
        : [...selectedCategories.value, id];

    applyFilters();
}

function preventClose(event: Event) {
    event.preventDefault();
}

function toggleSort(column: string) {
    sort.value = sort.value === column ? `-${column}` : column;
    applyFilters();
}

function sortIcon(column: string) {
    if (sort.value === column) {
        return ChevronUp;
    }

    if (sort.value === `-${column}`) {
        return ChevronDown;
    }

    return ChevronsUpDown;
}

function clearFilters() {
    search.value = '';
    selectedCategories.value = [];
    sort.value = 'name';
    applyFilters();
}

function goToPage(page: number) {
    applyFilters(page);
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

const showDialog = ref(false);
const editingExercise = ref<Exercise | null>(null);
const isEditing = computed(() => editingExercise.value !== null);

const form = useForm({
    name: '',
    exercise_category_id: '' as string,
    movement_pattern: 'none' as string,
});

function openCreate() {
    editingExercise.value = null;
    form.reset();
    form.clearErrors();
    showDialog.value = true;
}

function openEdit(exercise: Exercise) {
    editingExercise.value = exercise;
    form.name = exercise.name;
    form.exercise_category_id = String(exercise.exercise_category_id);
    form.movement_pattern = exercise.movement_pattern ?? 'none';
    form.clearErrors();
    showDialog.value = true;
}

function submitForm() {
    const url = isEditing.value
        ? update.url(editingExercise.value!.id)
        : store.url();

    const method = isEditing.value ? 'put' : 'post';

    form.transform((data) => ({
        name: data.name,
        exercise_category_id: data.exercise_category_id
            ? Number(data.exercise_category_id)
            : null,
        movement_pattern:
            data.movement_pattern === 'none' ? null : data.movement_pattern,
    }))[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            showDialog.value = false;
            editingExercise.value = null;
            form.reset();
        },
    });
}

function deleteExercise(exercise: Exercise) {
    if (!confirm('Are you sure you want to delete this exercise?')) {
        return;
    }

    router.delete(destroy.url(exercise.id), { preserveScroll: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Exercises',
        href: index(),
    },
];
</script>

<template>
    <Head title="Exercises" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-1 gap-4 p-4 lg:max-w-4xl">
            <Card class="self-start">
                <CardHeader>
                    <CardTitle class="leading-8">Exercises</CardTitle>

                    <CardAction>
                        <Button
                            size="icon-sm"
                            variant="outline"
                            class="cursor-pointer"
                            aria-label="Add exercise"
                            @click="openCreate()"
                        >
                            <Plus class="size-3" />
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center"
                    >
                        <div class="relative flex-1">
                            <Search
                                class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                            />

                            <Input
                                v-model="search"
                                type="search"
                                placeholder="Search exercises…"
                                class="pl-8"
                                @update:model-value="debouncedApply"
                            />
                        </div>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="w-full cursor-pointer justify-between sm:w-52"
                                    :disabled="loading"
                                >
                                    <span class="truncate">
                                        {{
                                            selectedCategories.length === 0
                                                ? 'All categories'
                                                : `${selectedCategories.length} selected`
                                        }}
                                    </span>

                                    <ChevronDown class="size-4 opacity-50" />
                                </Button>
                            </DropdownMenuTrigger>

                            <DropdownMenuContent class="w-52" align="start">
                                <DropdownMenuCheckboxItem
                                    v-for="category in categories"
                                    :key="category.id"
                                    :model-value="
                                        selectedCategories.includes(
                                            String(category.id),
                                        )
                                    "
                                    @select="preventClose"
                                    @update:model-value="
                                        toggleCategory(String(category.id))
                                    "
                                >
                                    {{ category.name }}
                                </DropdownMenuCheckboxItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Button
                            v-if="hasActiveFilters"
                            type="button"
                            variant="ghost"
                            class="cursor-pointer"
                            :disabled="loading"
                            @click="clearFilters()"
                        >
                            <X class="size-4" />
                            Clear
                        </Button>
                    </div>

                    <table
                        class="w-full text-left text-sm"
                        aria-label="Exercises"
                    >
                        <thead class="border-b text-muted-foreground">
                            <tr>
                                <th class="px-4 py-2 font-medium">
                                    <button
                                        type="button"
                                        class="inline-flex cursor-pointer items-center gap-1 hover:text-foreground disabled:cursor-default"
                                        :disabled="loading"
                                        @click="toggleSort('name')"
                                    >
                                        Name
                                        <component
                                            :is="sortIcon('name')"
                                            class="size-3.5"
                                        />
                                    </button>
                                </th>

                                <th class="px-4 py-2 font-medium">
                                    <button
                                        type="button"
                                        class="inline-flex cursor-pointer items-center gap-1 hover:text-foreground disabled:cursor-default"
                                        :disabled="loading"
                                        @click="toggleSort('category')"
                                    >
                                        Category
                                        <component
                                            :is="sortIcon('category')"
                                            class="size-3.5"
                                        />
                                    </button>
                                </th>

                                <th class="px-4 py-2 font-medium">
                                    <button
                                        type="button"
                                        class="inline-flex cursor-pointer items-center gap-1 hover:text-foreground disabled:cursor-default"
                                        :disabled="loading"
                                        @click="toggleSort('created_at')"
                                    >
                                        Created
                                        <component
                                            :is="sortIcon('created_at')"
                                            class="size-3.5"
                                        />
                                    </button>
                                </th>

                                <th class="px-4 py-2 font-medium">
                                    <button
                                        type="button"
                                        class="inline-flex cursor-pointer items-center gap-1 hover:text-foreground disabled:cursor-default"
                                        :disabled="loading"
                                        @click="toggleSort('updated_at')"
                                    >
                                        Updated
                                        <component
                                            :is="sortIcon('updated_at')"
                                            class="size-3.5"
                                        />
                                    </button>
                                </th>

                                <th class="w-0 px-4 py-2"></th>
                            </tr>
                        </thead>

                        <tbody :class="loading ? 'opacity-60' : ''">
                            <tr v-if="exercises.length === 0">
                                <td
                                    colspan="5"
                                    class="px-4 py-6 text-center text-muted-foreground"
                                >
                                    No exercises found.
                                </td>
                            </tr>

                            <tr
                                v-for="exercise in exercises"
                                :key="exercise.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/50"
                            >
                                <td class="px-4 py-3 font-medium">
                                    {{ exercise.name }}
                                </td>

                                <td class="px-4 py-3">
                                    <div
                                        class="flex flex-wrap items-center gap-1"
                                    >
                                        <Badge
                                            variant="secondary"
                                            class="text-xs"
                                        >
                                            {{ exercise.exerciseCategory.name }}
                                        </Badge>

                                        <Badge
                                            v-if="exercise.movement_pattern"
                                            variant="outline"
                                            class="text-xs"
                                        >
                                            {{ exercise.movement_pattern }}
                                        </Badge>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ formatDate(exercise.created_at) }}
                                </td>

                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ formatDate(exercise.updated_at) }}
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <button
                                                class="flex cursor-pointer items-center"
                                                aria-label="Actions"
                                            >
                                                <EllipsisVertical
                                                    class="size-4 text-muted-foreground"
                                                />
                                            </button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem
                                                class="cursor-pointer"
                                                @click="openEdit(exercise)"
                                            >
                                                <Pencil class="size-4" />
                                                Edit
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                class="cursor-pointer text-red-600 dark:text-red-400"
                                                @click="
                                                    deleteExercise(exercise)
                                                "
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

                    <div
                        class="flex flex-col items-center justify-between gap-2 text-sm text-muted-foreground sm:flex-row"
                    >
                        <span>
                            <template v-if="meta.total > 0">
                                Showing {{ meta.from }}–{{ meta.to }} of
                                {{ meta.total }}
                            </template>

                            <template v-else> No results </template>
                        </span>

                        <div class="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon-sm"
                                class="cursor-pointer"
                                aria-label="Previous page"
                                :disabled="loading || meta.current_page <= 1"
                                @click="goToPage(meta.current_page - 1)"
                            >
                                <ChevronLeft class="size-4" />
                            </Button>

                            <span>
                                Page {{ meta.current_page }} of
                                {{ meta.last_page }}
                            </span>

                            <Button
                                type="button"
                                variant="outline"
                                size="icon-sm"
                                class="cursor-pointer"
                                aria-label="Next page"
                                :disabled="
                                    loading ||
                                    meta.current_page >= meta.last_page
                                "
                                @click="goToPage(meta.current_page + 1)"
                            >
                                <ChevronRight class="size-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="showDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        isEditing ? 'Edit Exercise' : 'Add Exercise'
                    }}</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <fieldset :disabled="form.processing" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="exercise-name">Name</Label>

                            <Input
                                id="exercise-name"
                                v-model="form.name"
                                type="text"
                                maxlength="255"
                                autofocus
                            />

                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="exercise-category">Category</Label>

                            <Select v-model="form.exercise_category_id">
                                <SelectTrigger
                                    id="exercise-category"
                                    class="w-full"
                                >
                                    <SelectValue
                                        placeholder="Select a category"
                                    />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectItem
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="String(category.id)"
                                    >
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <InputError
                                :message="form.errors.exercise_category_id"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="exercise-movement-pattern">
                                Movement pattern
                            </Label>

                            <Select v-model="form.movement_pattern">
                                <SelectTrigger
                                    id="exercise-movement-pattern"
                                    class="w-full"
                                >
                                    <SelectValue
                                        placeholder="Select a movement pattern"
                                    />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectItem
                                        v-for="option in movementPatternOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <InputError
                                :message="form.errors.movement_pattern"
                            />
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                class="cursor-pointer"
                                @click="showDialog = false"
                            >
                                Cancel
                            </Button>

                            <Button type="submit" class="cursor-pointer">
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
