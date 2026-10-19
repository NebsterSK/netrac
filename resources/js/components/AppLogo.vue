<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <img src="/favicon.svg" alt="" class="aspect-square size-8 shrink-0" />

    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{
            page.props.name
        }}</span>
    </div>
</template>
