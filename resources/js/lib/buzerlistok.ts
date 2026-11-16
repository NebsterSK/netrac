type Week = App.Data.Buzerlistok.WeekData;

/** Today's date as `YYYY-MM-DD` in the browser's local time zone. */
export function todayString(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const date = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${date}`;
}

/**
 * Share of goal units that succeeded, rounded to a whole percent. Null when
 * nothing counts yet.
 *
 * Daily goals: one unit per goal-day, succeeded when success or partial. Past
 * days count every goal, so an unmarked one counts as missed; today counts only
 * what is already marked; future days are left out.
 *
 * Weekly goals: one unit per required time, and doing more earns no extra
 * credit. Completed times count straight away; the missing ones only count as
 * misses once the week is over.
 */
export function weekSuccessRate(week: Week, today: string): number | null {
    let counted = 0;
    let succeeded = 0;

    const isWeekOver = today > week.days[week.days.length - 1];

    for (const weeklyGoal of week.weekly_goals) {
        const credited = Math.min(weeklyGoal.completions, weeklyGoal.target);

        counted += isWeekOver ? weeklyGoal.target : credited;
        succeeded += credited;
    }

    for (const date of week.days) {
        if (date > today) {
            continue;
        }

        for (const goal of week.goals) {
            const status = goal.marks.find(
                (mark) => mark.marked_on === date,
            )?.status;

            if (date === today && status === undefined) {
                continue;
            }

            counted++;

            if (status === 'success' || status === 'partial') {
                succeeded++;
            }
        }
    }

    return counted === 0 ? null : Math.round((succeeded / counted) * 100);
}

/** Red at 0 %, through yellow, to green at 100 %. */
export function successRateColor(rate: number): string {
    return `hsl(${Math.round(rate * 1.2)} 75% 50%)`;
}

/** A weekly goal row in the week form; `key` keeps new rows (without an id) stable. */
export type WeeklyGoalField = {
    key: number;
    id: number | null;
    name: string;
    target: number;
};

let nextWeeklyGoalKey = 1;

/** Build a weekly goal row; pass an existing goal's values when editing. */
export function makeWeeklyGoalField(
    id: number | null = null,
    name = '',
    target = 1,
): WeeklyGoalField {
    return { key: nextWeeklyGoalKey++, id, name, target };
}

/** Weekly goal rows as the server expects them: trimmed, blank names dropped. */
export function weeklyGoalPayload(
    fields: WeeklyGoalField[],
): { id: number | null; name: string; target: number }[] {
    return fields
        .map((field) => ({
            id: field.id,
            name: field.name.trim(),
            target: Number(field.target),
        }))
        .filter((field) => field.name !== '');
}
