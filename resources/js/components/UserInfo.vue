<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user: User;
    showEmail?: boolean;
};

withDefaults(defineProps<Props>(), {
    showEmail: false,
});
</script>

<template>
    <Avatar class="size-8 overflow-hidden rounded-lg">
        <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />

        <AvatarFallback class="rounded-lg text-black dark:text-white">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>

        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{
            user.email
        }}</span>
    </div>
</template>
