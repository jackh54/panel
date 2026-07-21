import { action, Action } from 'easy-peasy';

export interface BrandingLink {
    label: string;
    url: string;
}

export interface SiteSettings {
    name: string;
    locale: string;
    recaptcha: {
        enabled: boolean;
        siteKey: string;
    };
    turnstile?: {
        enabled: boolean;
        siteKey: string;
    };
    branding?: {
        logo?: string;
        company?: string;
        url?: string;
        links?: BrandingLink[];
    };
    announcement?: {
        enabled: boolean;
        message: string;
        type: 'info' | 'warning' | 'danger';
    };
    sentry?: {
        dsn?: string | null;
        environment?: string;
        release?: string | null;
        tracesSampleRate?: number;
        replaysSessionSampleRate?: number;
        replaysOnErrorSampleRate?: number;
    };
}

export interface SettingsStore {
    data?: SiteSettings;
    setSettings: Action<SettingsStore, SiteSettings>;
}

const settings: SettingsStore = {
    data: undefined,

    setSettings: action((state, payload) => {
        state.data = payload;
    }),
};

export default settings;
