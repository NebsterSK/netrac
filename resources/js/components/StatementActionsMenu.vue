<script setup lang="ts">
import { CircleCheck, EllipsisVertical, Pencil, Trash2 } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

defineProps<{
    statement: App.Data.Finance.StatementData;
    label?: string;
}>();

const emit = defineEmits<{
    finalize: [];
    edit: [];
    delete: [];
}>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="-m-2 flex cursor-pointer items-center p-2"
                :aria-label="label ?? 'Actions'"
            >
                <EllipsisVertical class="size-4 text-muted-foreground" />
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="start">
            <DropdownMenuItem
                v-if="statement.is_draft"
                class="cursor-pointer"
                @click="emit('finalize')"
            >
                <CircleCheck class="size-4" />
                Finalize
            </DropdownMenuItem>

            <DropdownMenuItem class="cursor-pointer" @click="emit('edit')">
                <Pencil class="size-4" />
                Edit
            </DropdownMenuItem>

            <DropdownMenuItem
                class="cursor-pointer text-red-600 dark:text-red-400"
                @click="emit('delete')"
            >
                <Trash2 class="size-4" />
                Delete
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
