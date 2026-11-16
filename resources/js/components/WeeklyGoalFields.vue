<script setup lang="ts">
import { Plus, X } from 'lucide-vue-next';
import { nextTick, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { WeeklyGoalField } from '@/lib/buzerlistok';
import { makeWeeklyGoalField } from '@/lib/buzerlistok';

defineProps<{
    error?: string;
}>();

const fields = defineModel<WeeklyGoalField[]>({ required: true });

const container = ref<HTMLElement | null>(null);

/** Insert an empty weekly goal (at the end by default) and focus its name. */
async function addField(position = fields.value.length) {
    const next = [...fields.value];
    next.splice(position, 0, makeWeeklyGoalField());
    fields.value = next;

    await nextTick();

    container.value
        ?.querySelectorAll<HTMLInputElement>('[data-weekly-goal-input]')
        [position]?.focus();
}

/** Enter adds a weekly goal below the current one instead of submitting. */
function onEnter(event: KeyboardEvent, index: number) {
    if (event.isComposing) {
        return;
    }

    event.preventDefault();
    addField(index + 1);
}

function removeField(index: number) {
    fields.value = fields.value.filter((_, position) => position !== index);
}
</script>

<template>
    <div ref="container" class="space-y-2">
        <Label>Weekly goals</Label>

        <p class="text-xs text-muted-foreground">
            Done any day of the week, the set number of times.
        </p>

        <div
            v-for="(field, index) in fields"
            :key="field.key"
            class="flex items-center gap-2"
        >
            <Input
                v-model="field.name"
                placeholder="e.g. Shave, Exercise"
                aria-label="Weekly goal"
                data-weekly-goal-input
                @keydown.enter="onEnter($event, index)"
            />

            <div class="flex shrink-0 items-center gap-1">
                <Input
                    v-model.number="field.target"
                    type="number"
                    inputmode="numeric"
                    min="1"
                    max="99"
                    class="w-16 text-center [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                    aria-label="Times per week"
                    @keydown.enter="onEnter($event, index)"
                />

                <span class="text-sm text-muted-foreground">×</span>
            </div>

            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                class="cursor-pointer text-muted-foreground"
                aria-label="Remove weekly goal"
                @click="removeField(index)"
            >
                <X class="size-4" />
            </Button>
        </div>

        <InputError :message="error" />

        <Button
            type="button"
            variant="outline"
            size="sm"
            class="cursor-pointer"
            @click="addField()"
        >
            <Plus class="size-4" />
            Add weekly goal
        </Button>
    </div>
</template>
