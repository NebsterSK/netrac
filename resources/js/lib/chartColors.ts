// Known service → brand color. Matched case-insensitively; the longest matching
// key wins so "youtube music" beats "youtube".
const BRAND_COLORS: Record<string, string> = {
    netflix: '#e50914',
    youtube: '#ff0000',
    'youtube premium': '#ff0000',
    'youtube music': '#ff0000',
    'youtube tv': '#ff0000',
    spotify: '#1db954',
    disney: '#113ccf',
    'hbo max': '#7b2ff7',
    max: '#002be7',
    hulu: '#1ce783',
    'prime video': '#00a8e1',
    'amazon prime': '#00a8e1',
    'amazon music': '#00a8e1',
    amazon: '#ff9900',
    'apple tv': '#000000',
    'apple music': '#fa243c',
    'apple arcade': '#f14e50',
    'apple one': '#333333',
    icloud: '#3693f3',
    paramount: '#0064ff',
    peacock: '#05a081',
    crunchyroll: '#f47521',
    twitch: '#9146ff',
    deezer: '#feaa2d',
    tidal: '#00cfff',
    audible: '#f8991c',
    dropbox: '#0061ff',
    notion: '#111111',
    slack: '#4a154b',
    zoom: '#2d8cff',
    canva: '#00c4cc',
    figma: '#f24e1e',
    adobe: '#ff0000',
    'creative cloud': '#ff0000',
    microsoft: '#f25022',
    'microsoft 365': '#f25022',
    'office 365': '#f25022',
    'google one': '#4285f4',
    github: '#8957e5',
    'github copilot': '#8957e5',
    chatgpt: '#10a37f',
    openai: '#10a37f',
    claude: '#d97757',
    'xbox game pass': '#107c10',
    'game pass': '#107c10',
    xbox: '#107c10',
    'playstation plus': '#0070d1',
    playstation: '#0070d1',
    nintendo: '#e60012',
    steam: '#66c0f4',
    strava: '#fc4c02',
    duolingo: '#58cc02',
    headspace: '#f47d31',
    calm: '#2a6ebb',
    nordvpn: '#4687ff',
    expressvpn: '#da3940',
    patreon: '#ff424d',
    substack: '#ff6719',
    medium: '#000000',
    linkedin: '#0a66c2',
    'new york times': '#000000',
    nyt: '#000000',
};

const BRAND_KEYS = Object.keys(BRAND_COLORS).sort(
    (keyA, keyB) => keyB.length - keyA.length,
);

// Fallback categorical palette (validated via the dataviz skill) for services
// with no known brand color; the 8th slot is never cycled.
export const SERIES_LIGHT = [
    '#2a78d6',
    '#1baf7a',
    '#eda100',
    '#008300',
    '#4a3aa7',
    '#e34948',
    '#e87ba4',
    '#eb6834',
];

export const SERIES_DARK = [
    '#3987e5',
    '#199e70',
    '#c98500',
    '#008300',
    '#9085e9',
    '#e66767',
    '#d55181',
    '#d95926',
];

export const OTHER_COLOR = '#898781';

function brandColor(label: string): string | null {
    const normalized = label.toLowerCase().trim();

    if (BRAND_COLORS[normalized]) {
        return BRAND_COLORS[normalized];
    }

    const match = BRAND_KEYS.find((key) => normalized.includes(key));

    return match ? BRAND_COLORS[match] : null;
}

function relativeLuminance(hex: string): number {
    const value = hex.replace('#', '');
    const red = parseInt(value.slice(0, 2), 16);
    const green = parseInt(value.slice(2, 4), 16);
    const blue = parseInt(value.slice(4, 6), 16);

    return (0.299 * red + 0.587 * green + 0.114 * blue) / 255;
}

/** Lift near-black brand colors so they stay visible on the dark surface. */
function lightenForDark(hex: string, amount = 0.45): string {
    const value = hex.replace('#', '');
    const channels = [
        parseInt(value.slice(0, 2), 16),
        parseInt(value.slice(2, 4), 16),
        parseInt(value.slice(4, 6), 16),
    ].map((channel) =>
        Math.round(channel + (255 - channel) * amount)
            .toString(16)
            .padStart(2, '0'),
    );

    return `#${channels.join('')}`;
}

export function sliceColor(
    label: string,
    index: number,
    isDark: boolean,
): string {
    if (label === 'Other') {
        return OTHER_COLOR;
    }

    const brand = brandColor(label);

    if (brand) {
        return isDark && relativeLuminance(brand) < 0.22
            ? lightenForDark(brand)
            : brand;
    }

    const palette = isDark ? SERIES_DARK : SERIES_LIGHT;

    return palette[index % palette.length];
}

export function seriesColor(index: number, isDark: boolean): string {
    const palette = isDark ? SERIES_DARK : SERIES_LIGHT;

    return palette[index % palette.length];
}
