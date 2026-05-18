<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { EllipsisVertical, Pencil, Plus, Trash2 } from 'lucide-vue-next';
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
import { destroy, index, store, update } from '@/routes/health/categories';
import type { BreadcrumbItem } from '@/types';

type Category = App.Data.Health.CategoryData;

defineProps<{
    categories: Category[];
}>();

const showDialog = ref(false);
const editingCategory = ref<Category | null>(null);
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

function openEdit(category: Category) {
    editingCategory.value = category;
    form.name = category.name;
    form.clearErrors();
    showDialog.value = true;
}

function submitForm() {
    const url = isEditing.value
        ? update.url(editingCategory.value!.id)
        : store.url();

    const method = isEditing.value ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => {
            showDialog.value = false;
            editingCategory.value = null;
            form.reset();
        },
    });
}

function deleteCategory(category: Category) {
    if (!confirm('Are you sure you want to delete this category?')) {
        return;
    }

    router.delete(destroy.url(category.id));
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Categories',
        href: index(),
    },
];
</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-1 gap-4 p-4 lg:max-w-2xl">
            <Card class="self-start">
                <CardHeader>
                    <CardTitle class="leading-8">Categories</CardTitle>

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
                    <p
                        v-if="categories.length === 0"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        No categories yet. Add one to get started.
                    </p>

                    <table
                        v-else
                        class="w-full text-left text-sm"
                        aria-label="Categories"
                    >
                        <tbody>
                            <tr
                                v-for="category in categories"
                                :key="category.id"
                                class="group/row border-b transition-colors last:border-0 hover:bg-muted/50"
                            >
                                <td class="w-0 pl-4">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <button
                                                class="flex h-full cursor-pointer items-center"
                                                aria-label="Actions"
                                            >
                                                <EllipsisVertical
                                                    class="size-4 text-muted-foreground"
                                                />
                                            </button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="start">
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
                                </td>

                                <td class="px-4 py-3">
                                    {{ category.name }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
