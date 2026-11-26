<script setup lang="ts">
import { Flame, Trophy } from 'lucide-vue-next';
import { computed } from 'vue';
import { greenDayStreaks, todayString } from '@/lib/buzerlistok';

const props = withDefaults(
    defineProps<{
        dayResults: App.Data.Buzerlistok.DayResultData[];
        /** `compact` is the one-line version for the dashboard card header. */
        variant?: 'full' | 'compact';
    }>(),
    { variant: 'full' },
);

const streaks = computed(() =>
    greenDayStreaks(props.dayResults, todayString()),
);

function dayCount(days: number): string {
    return `${days} ${days === 1 ? 'day' : 'days'}`;
}

function formatDate(date: string, withYear: boolean): string {
    const [year, month, day] = date.split('-').map(Number);

    return new Date(year, month - 1, day).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        ...(withYear ? { year: 'numeric' } : {}),
    });
}

const longestRange = computed(() => {
    const longest = streaks.value.longest;

    if (!longest) {
        return null;
    }

    const sameYear = longest.from.slice(0, 4) === longest.to.slice(0, 4);

    return `${formatDate(longest.from, !sameYear)} – ${formatDate(longest.to, true)}`;
});
</script>

<template>
    <div
        v-if="variant === 'compact'"
        class="flex items-center gap-3 text-sm text-muted-foreground"
    >
        <span
            class="inline-flex items-center gap-1"
            :title="`Current green streak: ${dayCount(streaks.current)}`"
        >
            <Flame class="size-4 text-orange-500" />

            <span class="font-mono font-semibold text-foreground">{{
                streaks.current
            }}</span>
            d
        </span>

        <span
            v-if="streaks.longest"
            class="inline-flex items-center gap-1"
            :title="`Longest green streak: ${dayCount(streaks.longest.days)}, ${longestRange}`"
        >
            <Trophy class="size-4 text-yellow-500" />

            <span class="font-mono font-semibold text-foreground">{{
                streaks.longest.days
            }}</span>
            d
        </span>
    </div>

    <div v-else class="grid grid-cols-2 gap-3">
        <div class="flex items-start gap-3">
            <Flame class="mt-0.5 size-5 shrink-0 text-orange-500" />

            <div class="min-w-0">
                <p class="text-xs text-muted-foreground">Current streak</p>

                <p class="text-lg font-semibold">
                    {{ dayCount(streaks.current) }}
                </p>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <Trophy class="mt-0.5 size-5 shrink-0 text-yellow-500" />

            <div class="min-w-0">
                <p class="text-xs text-muted-foreground">Longest streak</p>

                <template v-if="streaks.longest">
                    <p class="text-lg font-semibold">
                        {{ dayCount(streaks.longest.days) }}
                    </p>

                    <p class="text-xs text-muted-foreground">
                        {{ longestRange }}
                    </p>
                </template>

                <p v-else class="text-sm text-muted-foreground">
                    No green day yet
                </p>
            </div>
        </div>
    </div>
</template>
