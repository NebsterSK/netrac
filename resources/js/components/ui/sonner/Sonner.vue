<script setup lang="ts">
import 'vue-sonner/style.css';

import { useAppearance } from '@/composables/useAppearance';
import { Toaster as Sonner, type ToasterProps } from 'vue-sonner';

const props = defineProps<ToasterProps>();

const { resolvedAppearance } = useAppearance();
</script>

<template>
    <Sonner :theme="resolvedAppearance" v-bind="props" />
</template>
