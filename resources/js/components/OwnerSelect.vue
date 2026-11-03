<script setup lang="ts">
import { computed, onMounted } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useOwners } from '@/composables/useOwners';

const props = withDefaults(
    defineProps<{
        id?: string;
        disabled?: boolean;
    }>(),
    {
        id: undefined,
        disabled: false,
    },
);

const model = defineModel<number | null>({ default: null });

const { owners, defaultOwnerId } = useOwners();

const selectedValue = computed({
    get: () => (model.value === null ? '' : String(model.value)),
    set: (value: string) => {
        model.value = value === '' ? null : Number(value);
    },
});

onMounted(() => {
    if (model.value === null) {
        model.value = defaultOwnerId.value;
    }
});
</script>

<template>
    <Select v-model="selectedValue" :disabled="props.disabled">
        <SelectTrigger :id="props.id" class="w-full">
            <SelectValue placeholder="Select an owner" />
        </SelectTrigger>

        <SelectContent>
            <SelectItem
                v-for="owner in owners"
                :key="owner.id"
                :value="String(owner.id)"
            >
                <span
                    class="size-2.5 shrink-0 rounded-full"
                    :style="{ backgroundColor: owner.color }"
                />

                {{ owner.name }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
