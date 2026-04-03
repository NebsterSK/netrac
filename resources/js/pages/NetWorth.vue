<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import netWorth from '@/routes/net-worth';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Net Worth',
        href: netWorth.index(),
    },
];
</script>

<template>
    <Head title="Net Worth" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
        </div>
    </AppLayout>
</template>
