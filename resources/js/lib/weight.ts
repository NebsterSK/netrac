const DAY_MS = 86_400_000;

/** `YYYY-MM-DD` as whole days since the epoch, so a chart's x axis is linear in time. */
export function dayNumber(date: string): number {
    const [year, month, day] = date.split('-').map(Number);

    return Date.UTC(year, month - 1, day) / DAY_MS;
}

/** European `d. m. yyyy` label for a day number. */
export function formatDay(dayValue: number, withYear = true): string {
    const date = new Date(dayValue * DAY_MS);
    const label = `${date.getUTCDate()}. ${date.getUTCMonth() + 1}.`;

    return withYear ? `${label} ${date.getUTCFullYear()}` : label;
}

export function formatWeight(weight: number): string {
    return weight.toLocaleString('sk-SK', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    });
}
