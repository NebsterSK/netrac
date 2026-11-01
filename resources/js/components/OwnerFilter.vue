<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useOwners } from '@/composables/useOwners';
import { cn } from '@/lib/utils';
import { update } from '@/routes/finance/owner-filter';

const { owners, ownerFilter } = useOwners();

const processing = ref(false);

function selectOwner(ownerId: number | null) {
    if (processing.value || ownerFilter.value === ownerId) {
        return;
    }

    router.put(
        update(),
        { owner_id: ownerId },
        {
            preserveScroll: true,
            onStart: () => {
                processing.value = true;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

function segmentClass(isActive: boolean): string {
    return cn(
        'inline-flex h-10 min-w-10 cursor-pointer items-center justify-center gap-1.5 rounded-md px-2.5 text-sm font-medium whitespace-nowrap transition-colors disabled:cursor-default disabled:opacity-60 sm:h-8 sm:px-3',
        isActive
            ? 'bg-background text-foreground shadow-xs'
            : 'text-muted-foreground hover:text-foreground',
    );
}
</script>

<template>
    <div
        v-if="owners.length > 0"
        role="radiogroup"
        aria-label="Owner filter"
        class="inline-flex max-w-full shrink-0 items-center gap-0.5 overflow-x-auto rounded-lg bg-muted p-0.5"
    >
        <button
            type="button"
            role="radio"
            :aria-checked="ownerFilter === null"
            :class="segmentClass(ownerFilter === null)"
            :disabled="processing"
            @click="selectOwner(null)"
        >
            All
        </button>

        <button
            v-for="owner in owners"
            :key="owner.id"
            type="button"
            role="radio"
            :aria-checked="ownerFilter === owner.id"
            :aria-label="owner.name"
            :class="segmentClass(ownerFilter === owner.id)"
            :disabled="processing"
            @click="selectOwner(owner.id)"
        >
            <span
                class="size-2.5 shrink-0 rounded-full"
                :style="{ backgroundColor: owner.color }"
            />

            <span class="max-w-20 truncate">{{ owner.name }}</span>
        </button>
    </div>
</template>
