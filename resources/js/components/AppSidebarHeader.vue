<script setup lang="ts">
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import OwnerFilter from '@/components/OwnerFilter.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import finance from '@/routes/finance';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

const ownerFilterRoutes = [
    finance.netWorth.index(),
    finance.monthlyBalance.index(),
    finance.assets.index(),
    finance.expenses.index(),
    finance.expenseCategories.index(),
];

const showOwnerFilter = computed(
    () =>
        isCurrentUrl(dashboard()) ||
        ownerFilterRoutes.some((route) => isCurrentOrParentUrl(route)),
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1 shrink-0" />

            <div
                v-if="breadcrumbs && breadcrumbs.length > 0"
                class="min-w-0"
                :class="{ 'hidden sm:block': showOwnerFilter }"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>

        <OwnerFilter v-if="showOwnerFilter" class="ml-auto" />
    </header>
</template>
