<script setup lang="ts">
defineProps<{
    owner: App.Data.Finance.OwnerData;
}>();
</script>

<template>
    <span
        class="inline-flex w-fit shrink-0 items-center gap-1.5 rounded-full border px-2 py-0.5 text-xs font-medium whitespace-nowrap text-foreground"
        :style="{
            borderColor: `color-mix(in srgb, ${owner.color} 45%, transparent)`,
            backgroundColor: `color-mix(in srgb, ${owner.color} 14%, transparent)`,
        }"
    >
        <span
            class="size-2 shrink-0 rounded-full"
            :style="{ backgroundColor: owner.color }"
        />

        {{ owner.name }}
    </span>
</template>
