<script setup lang="ts">
import { Ban, Check, Pipette } from 'lucide-vue-next';
import { computed } from 'vue';

const PRESET_COLORS = [
    '#ef4444',
    '#f97316',
    '#eab308',
    '#22c55e',
    '#14b8a6',
    '#06b6d4',
    '#3b82f6',
    '#6366f1',
    '#a855f7',
    '#ec4899',
];

const model = defineModel<string | null>({ required: true });

defineProps<{
    id?: string;
}>();

const isCustom = computed(
    () => model.value !== null && !PRESET_COLORS.includes(model.value),
);

function updateCustom(event: Event) {
    model.value = (event.target as HTMLInputElement).value.toLowerCase();
}
</script>

<template>
    <div :id="id" class="flex flex-wrap items-center gap-2" role="radiogroup">
        <button
            type="button"
            role="radio"
            :aria-checked="model === null"
            aria-label="Automatic color"
            title="Automatic"
            class="flex size-7 cursor-pointer items-center justify-center rounded-full border border-dashed text-muted-foreground transition-shadow"
            :class="{
                'ring-2 ring-ring ring-offset-2 ring-offset-background':
                    model === null,
            }"
            @click="model = null"
        >
            <Ban class="size-3.5" />
        </button>

        <button
            v-for="color in PRESET_COLORS"
            :key="color"
            type="button"
            role="radio"
            :aria-checked="model === color"
            :aria-label="`Color ${color}`"
            :title="color"
            class="flex size-7 cursor-pointer items-center justify-center rounded-full transition-shadow"
            :class="{
                'ring-2 ring-ring ring-offset-2 ring-offset-background':
                    model === color,
            }"
            :style="{ backgroundColor: color }"
            @click="model = color"
        >
            <Check v-if="model === color" class="size-3.5 text-white" />
        </button>

        <label
            class="relative flex size-7 cursor-pointer items-center justify-center rounded-full transition-shadow"
            :class="{
                'ring-2 ring-ring ring-offset-2 ring-offset-background':
                    isCustom,
            }"
            :style="{
                background: isCustom
                    ? model!
                    : 'conic-gradient(#ef4444, #eab308, #22c55e, #06b6d4, #3b82f6, #a855f7, #ef4444)',
            }"
            :title="isCustom ? model! : 'Custom color'"
        >
            <Pipette class="size-3.5 text-white drop-shadow" />

            <input
                type="color"
                class="absolute inset-0 size-full cursor-pointer opacity-0"
                aria-label="Custom color"
                :value="isCustom ? model! : '#000000'"
                @input="updateCustom"
            />
        </label>
    </div>
</template>
