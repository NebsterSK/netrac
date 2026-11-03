type Week = App.Data.Buzerlistok.WeekData;

/** Today's date as `YYYY-MM-DD` in the browser's local time zone. */
export function todayString(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const date = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${date}`;
}

/**
 * Share of goal-days that succeeded (success or partial), rounded to a whole
 * percent. Past days count every goal, so an unmarked one counts as missed;
 * today counts only what is already marked; future days are left out. Null
 * when nothing counts yet.
 */
export function weekSuccessRate(week: Week, today: string): number | null {
    let counted = 0;
    let succeeded = 0;

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
