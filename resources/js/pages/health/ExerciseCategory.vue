<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    EllipsisVertical,
    GripVertical,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import exerciseCategories from '@/routes/health/exercise-categories';
import type { BreadcrumbItem } from '@/types';

type ExerciseCategory = App.Data.Health.ExerciseCategoryData;

const props = defineProps<{
    categories: ExerciseCategory[];
}>();

const addedLevels = ref(0);
const draggingId = ref<number | null>(null);
const dragOverPriority = ref<number | null>(null);

const maxPriority = computed(() =>
    props.categories.reduce(
        (max, category) => Math.max(max, category.priority),
        1,
    ),
);

const lanes = computed(() =>
    Array.from(
        { length: maxPriority.value + addedLevels.value },
        (_, index) => {
            const priority = index + 1;

            return {
                priority,
                categories: props.categories
                    .filter((category) => category.priority === priority)
                    .sort((categoryA, categoryB) =>
                        categoryA.name.localeCompare(categoryB.name),
                    ),
            };
        },
    ),
);

function addLevel() {
    addedLevels.value += 1;
}

function onDragStart(id: number, event: DragEvent) {
    draggingId.value = id;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(id));
    }
}

function onDragEnd() {
    draggingId.value = null;
    dragOverPriority.value = null;
}

function onDrop(priority: number) {
    const id = draggingId.value;
    onDragEnd();

    if (id === null) {
        return;
    }

    const category = props.categories.find((item) => item.id === id);

    if (!category || category.priority === priority) {
        return;
    }

    router.patch(
        exerciseCategories.priority.update.url(id),
        { priority },
        {
            preserveScroll: true,
            onSuccess: () => {
                addedLevels.value = 0;
            },
        },
    );
}

const showDialog = ref(false);
const editingCategory = ref<ExerciseCategory | null>(null);
const isEditing = computed(() => editingCategory.value !== null);

const form = useForm({
    name: '',
});

function openCreate() {
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
    showDialog.value = true;
}

function openEdit(category: ExerciseCategory) {
    editingCategory.value = category;
    form.name = category.name;
    form.clearErrors();
    showDialog.value = true;
}

function submitForm() {
    const url = isEditing.value
        ? exerciseCategories.update.url(editingCategory.value!.id)
        : exerciseCategories.store.url();

    const method = isEditing.value ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => {
            showDialog.value = false;
            editingCategory.value = null;
            form.reset();
        },
    });
}

function deleteCategory(category: ExerciseCategory) {
    if (!confirm('Are you sure you want to delete this category?')) {
        return;
    }

    router.delete(exerciseCategories.destroy.url(category.id));
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Exercise Categories',
        href: exerciseCategories.index(),
    },
];
</script>

<template>
    <Head title="Exercise Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-1 gap-4 p-4 lg:max-w-2xl">
            <Card class="self-start">
                <CardHeader>
                    <CardTitle class="leading-8">Exercise Categories</CardTitle>

                    <CardAction class="flex items-center gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            class="cursor-pointer"
                            @click="addLevel()"
                        >
                            <Plus class="size-3" />
                            Level
                        </Button>

                        <Button
                            size="icon-sm"
                            variant="outline"
                            class="cursor-pointer"
                            aria-label="Add category"
                            @click="openCreate()"
                        >
                            <Plus class="size-3" />
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-3">
                    <p
                        v-if="categories.length === 0"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        No categories yet. Add one to get started.
                    </p>

                    <div
                        v-for="lane in lanes"
                        :key="lane.priority"
                        class="rounded-md border transition-colors"
                        :class="
                            dragOverPriority === lane.priority
                                ? 'border-primary bg-primary/5'
                                : 'border-dashed'
                        "
                        @dragover.prevent="dragOverPriority = lane.priority"
                        @drop.prevent="onDrop(lane.priority)"
                    >
                        <div
                            class="px-3 pt-2 text-xs font-medium text-muted-foreground"
                        >
                            Priority {{ lane.priority }}
                        </div>

                        <div class="flex min-h-12 flex-wrap gap-2 p-3">
                            <p
                                v-if="lane.categories.length === 0"
                                class="self-center text-sm text-muted-foreground"
                            >
                                Drop a category here
                            </p>

                            <div
                                v-for="category in lane.categories"
                                :key="category.id"
                                draggable="true"
                                class="flex items-center gap-2 rounded-md border bg-muted/30 py-2 pr-1 pl-2 transition-opacity"
                                :class="
                                    draggingId === category.id
                                        ? 'opacity-50'
                                        : ''
                                "
                                @dragstart="onDragStart(category.id, $event)"
                                @dragend="onDragEnd()"
                            >
                                <GripVertical
                                    class="size-4 shrink-0 cursor-grab text-muted-foreground"
                                />

                                <span class="font-medium">{{
                                    category.name
                                }}</span>

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
                                            @click="openEdit(category)"
                                        >
                                            <Pencil class="size-4" />
                                            Edit
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            class="cursor-pointer text-red-600 dark:text-red-400"
                                            @click="deleteCategory(category)"
                                        >
                                            <Trash2 class="size-4" />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="showDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        isEditing ? 'Edit Category' : 'Add Category'
                    }}</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <fieldset :disabled="form.processing" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="category-name">Name</Label>

                            <Input
                                id="category-name"
                                v-model="form.name"
                                type="text"
                                maxlength="255"
                                autofocus
                            />

                            <InputError :message="form.errors.name" />
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
