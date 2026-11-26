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

type DayResult = App.Data.Buzerlistok.DayResultData;

export type Streak = { days: number; from: string; to: string };

export type Streaks = {
    /** Green days in a row up to today (or yesterday while today is still open). */
    current: number;
    /** The longest run of green days ever (the latest one on a tie), or null before the first green day. */
    longest: Streak | null;
};

/** `YYYY-MM-DD` of the day before the given one. */
function previousDay(date: string): string {
    const [year, month, day] = date.split('-').map(Number);
    const previous = new Date(year, month - 1, day - 1);
    const previousMonth = String(previous.getMonth() + 1).padStart(2, '0');
    const previousDate = String(previous.getDate()).padStart(2, '0');

    return `${previous.getFullYear()}-${previousMonth}-${previousDate}`;
}

/**
 * Green-day streaks from every tracked day. A day is green when all its daily
 * goals succeeded (green or blue). A missed, failed or untracked day breaks a
 * streak, except today, which doesn't break the current streak while it's
 * still open.
 */
export function greenDayStreaks(results: DayResult[], today: string): Streaks {
    const resultByDate = new Map(
        results.map((entry) => [entry.date, entry.result]),
    );

    let longest = null as Streak | null;
    let run = null as Streak | null;

    for (const entry of [...results].sort((left, right) =>
        left.date.localeCompare(right.date),
    )) {
        if (entry.date > today) {
            break;
        }

        if (entry.result !== 'success') {
            run = null;
            continue;
        }

        run =
            run !== null && run.to === previousDay(entry.date)
                ? { ...run, days: run.days + 1, to: entry.date }
                : { days: 1, from: entry.date, to: entry.date };

        // `>=` so a tie goes to the most recent streak.
        if (longest === null || run.days >= longest.days) {
            longest = run;
        }
    }

    let current = 0;
    let date = today;

    if (resultByDate.get(today) === 'fail') {
        return { current: 0, longest };
    }

    if (resultByDate.get(today) !== 'success') {
        date = previousDay(today);
    }

    while (resultByDate.get(date) === 'success') {
        current++;
        date = previousDay(date);
    }

    return { current, longest };
}
