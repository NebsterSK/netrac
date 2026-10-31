<script setup lang="ts">
import type { DateValue } from '@internationalized/date';
import { parseDate } from '@internationalized/date';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import {
    CalendarCell,
    CalendarCellTrigger,
    CalendarGrid,
    CalendarGridBody,
    CalendarGridHead,
    CalendarGridRow,
    CalendarHeadCell,
    CalendarHeader,
    CalendarHeading,
    CalendarNext,
    CalendarPrev,
    CalendarRoot,
} from 'reka-ui';
import { computed, ref } from 'vue';
import { buttonVariants } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const props = defineProps<{
    id?: string;
    disabled?: boolean;
    /** Week starts (`YYYY-MM-DD`) that are already used and cannot be picked. */
    takenWeekStarts?: string[];
}>();

/** The Monday the week starts on, as `YYYY-MM-DD`. */
const model = defineModel<string>({ required: true });

const open = ref(false);

const selected = computed<DateValue | undefined>(() => {
    try {
        return model.value ? parseDate(model.value) : undefined;
    } catch {
        return undefined;
    }
});

function isMonday(date: DateValue): boolean {
    return new Date(date.year, date.month - 1, date.day).getDay() === 1;
}

const taken = computed(() => new Set(props.takenWeekStarts ?? []));

function isUnavailable(date: DateValue): boolean {
    return !isMonday(date) || taken.value.has(date.toString());
}

function select(value: DateValue | DateValue[] | undefined | null) {
    if (!value || Array.isArray(value)) {
        return;
    }

    model.value = value.toString();
    open.value = false;
}

const label = computed(() => {
    if (!selected.value) {
        return 'Pick a week';
    }

    const start = new Date(
        selected.value.year,
        selected.value.month - 1,
        selected.value.day,
    );
    const end = new Date(start);
    end.setDate(start.getDate() + 6);

    const startLabel = start.toLocaleDateString('en-GB', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
    const endLabel = `${end.toLocaleDateString('en-GB', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    })} ${end.getFullYear()}`;

    return `${startLabel} – ${endLabel}`;
});
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <button
                :id="props.id"
                type="button"
                :disabled="props.disabled"
                class="flex h-9 w-full cursor-pointer items-center gap-2 rounded-md border border-input bg-transparent px-3 py-1 text-left text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                :class="selected ? '' : 'text-muted-foreground'"
            >
                <CalendarDays class="size-4 shrink-0 text-muted-foreground" />

                <span class="truncate">{{ label }}</span>
            </button>
        </PopoverTrigger>

        <PopoverContent class="w-auto p-3" align="start">
            <CalendarRoot
                v-slot="{ grid, weekDays }"
                :model-value="selected"
                :default-placeholder="selected"
                :week-starts-on="1"
                locale="en-GB"
                weekday-format="short"
                fixed-weeks
                prevent-deselect
                :is-date-disabled="isUnavailable"
                initial-focus
                @update:model-value="select"
            >
                <CalendarHeader
                    class="relative flex items-center justify-between pb-3"
                >
                    <CalendarPrev
                        :class="
                            cn(
                                buttonVariants({
                                    variant: 'outline',
                                    size: 'icon-sm',
                                }),
                                'cursor-pointer',
                            )
                        "
                        aria-label="Previous month"
                    >
                        <ChevronLeft class="size-4" />
                    </CalendarPrev>

                    <CalendarHeading class="text-sm font-medium" />

                    <CalendarNext
                        :class="
                            cn(
                                buttonVariants({
                                    variant: 'outline',
                                    size: 'icon-sm',
                                }),
                                'cursor-pointer',
                            )
                        "
                        aria-label="Next month"
                    >
                        <ChevronRight class="size-4" />
                    </CalendarNext>
                </CalendarHeader>

                <CalendarGrid
                    v-for="month in grid"
                    :key="month.value.toString()"
                    class="w-full border-collapse"
                >
                    <CalendarGridHead>
                        <CalendarGridRow class="flex">
                            <CalendarHeadCell
                                v-for="day in weekDays"
                                :key="day"
                                class="w-9 text-[0.8rem] font-normal text-muted-foreground"
                            >
                                {{ day }}
                            </CalendarHeadCell>
                        </CalendarGridRow>
                    </CalendarGridHead>

                    <CalendarGridBody>
                        <CalendarGridRow
                            v-for="(weekDates, index) in month.rows"
                            :key="`week-${index}`"
                            class="mt-1 flex w-full"
                        >
                            <CalendarCell
                                v-for="weekDate in weekDates"
                                :key="weekDate.toString()"
                                :date="weekDate"
                                class="relative p-0 text-center text-sm"
                            >
                                <CalendarCellTrigger
                                    :day="weekDate"
                                    :month="month.value"
                                    :class="
                                        taken.has(weekDate.toString())
                                            ? 'line-through'
                                            : undefined
                                    "
                                    :title="
                                        taken.has(weekDate.toString())
                                            ? 'This week already exists'
                                            : undefined
                                    "
                                    class="inline-flex size-9 cursor-pointer items-center justify-center rounded-md text-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none data-[disabled]:pointer-events-none data-[disabled]:text-muted-foreground data-[disabled]:opacity-40 data-[outside-view]:text-muted-foreground data-[selected]:bg-primary data-[selected]:text-primary-foreground data-[today]:not-[[data-selected]]:font-semibold data-[today]:not-[[data-selected]]:underline data-[today]:not-[[data-selected]]:underline-offset-4"
                                />
                            </CalendarCell>
                        </CalendarGridRow>
                    </CalendarGridBody>
                </CalendarGrid>
            </CalendarRoot>
        </PopoverContent>
    </Popover>
</template>
