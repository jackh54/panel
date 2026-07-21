import * as Sentry from '@sentry/react';
import { SiteSettings } from '@/state/settings';

interface ExtendedWindow extends Window {
    SiteConfiguration?: SiteSettings;
    PterodactylUser?: {
        uuid: string;
        username: string;
        email: string;
        name_first?: string;
        name_last?: string;
    };
}

/**
 * Initialize browser Sentry when a frontend DSN is configured.
 */
export default function initSentry(): void {
    const sentry = (window as ExtendedWindow).SiteConfiguration?.sentry;
    const dsn = typeof sentry?.dsn === 'string' ? sentry.dsn.trim() : '';

    if (!dsn) {
        return;
    }

    Sentry.init({
        dsn,
        environment: sentry?.environment || undefined,
        release: sentry?.release || undefined,
        integrations: [
            Sentry.browserTracingIntegration(),
            Sentry.replayIntegration(),
            Sentry.feedbackIntegration({
                colorScheme: 'system',
                buttonLabel: 'Report a Bug',
                submitButtonLabel: 'Send Bug Report',
                formTitle: 'Report a Bug',
                messagePlaceholder: 'What went wrong? What were you trying to do?',
                enableScreenshot: true,
            }),
        ],
        tracesSampleRate: sentry?.tracesSampleRate ?? 0.1,
        replaysSessionSampleRate: sentry?.replaysSessionSampleRate ?? 0,
        replaysOnErrorSampleRate: sentry?.replaysOnErrorSampleRate ?? 1,
    });

    const user = (window as ExtendedWindow).PterodactylUser;
    if (user?.uuid) {
        const name = [user.name_first, user.name_last].filter(Boolean).join(' ').trim();
        Sentry.setUser({
            id: user.uuid,
            email: user.email,
            username: user.username,
            ...(name ? { name } : {}),
        });
    }
}
