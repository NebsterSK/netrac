<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { EllipsisVertical, Pencil, Plus, Trash2 } from 'lucide-vue-next';
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
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import {
    destroy,
    index,
    store,
    update,
} from '@/routes/finance/expense-categories';
import type { BreadcrumbItem } from '@/types';

type ExpenseCategory = App.Data.Finance.ExpenseCategoryData;

defineProps<{
    categories: ExpenseCategory[];
}>();

const showDialog = ref(false);
const editingCategory = ref<ExpenseCategory | null>(null);
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

function openEdit(category: ExpenseCategory) {
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
        preserveScroll: true,
        onSuccess: () => {
            showDialog.value = false;
            editingCategory.value = null;
            form.reset();
        },
    });
}

function deleteCategory(category: ExpenseCategory) {
    const note =
        category.expenses_count && category.expenses_count > 0
            ? ` This also deletes ${category.expenses_count} expense${category.expenses_count === 1 ? '' : 's'}.`
            : '';

    if (!confirm(`Are you sure you want to delete this category?${note}`)) {
        return;
    }

    router.delete(destroy.url(category.id), { preserveScroll: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Expense Categories',
        href: index(),
    },
];
</script>

<template>
    <Head title="Expense Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-1 gap-4 p-4 lg:max-w-2xl">
            <Card class="self-start">
                <CardHeader>
                    <CardTitle class="leading-8">Expense Categories</CardTitle>

                    <CardAction>
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

                <CardContent>
                    <table
                        class="w-full text-left text-sm"
                        aria-label="Expense categories"
                    >
                        <thead class="border-b text-muted-foreground">
                            <tr>
                                <th class="px-4 py-2 font-medium">Name</th>

                                <th class="px-4 py-2 font-medium">Expenses</th>

                                <th class="w-0 px-4 py-2"></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-if="categories.length === 0">
                                <td
                                    colspan="3"
                                    class="px-4 py-6 text-center text-muted-foreground"
                                >
                                    No categories yet.
                                </td>
                            </tr>

                            <tr
                                v-for="category in categories"
                                :key="category.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/50"
                            >
                                <td class="px-4 py-3 font-medium">
                                    {{ category.name }}
                                </td>

                                <td class="px-4 py-3">
                                    <Badge variant="secondary" class="text-xs">
                                        {{ category.expenses_count ?? 0 }}
                                    </Badge>
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
                                                @click="openEdit(category)"
                                            >
                                                <Pencil class="size-4" />
                                                Edit
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                class="cursor-pointer text-red-600 dark:text-red-400"
                                                @click="
                                                    deleteCategory(category)
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
