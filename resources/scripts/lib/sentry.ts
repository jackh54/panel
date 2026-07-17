import * as Sentry from '@sentry/react';
import { SiteSettings } from '@/state/settings';

interface ExtendedWindow extends Window {
    SiteConfiguration?: SiteSettings;
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
        integrations: [Sentry.browserTracingIntegration(), Sentry.replayIntegration()],
        tracesSampleRate: sentry?.tracesSampleRate ?? 0.1,
        replaysSessionSampleRate: sentry?.replaysSessionSampleRate ?? 0,
        replaysOnErrorSampleRate: sentry?.replaysOnErrorSampleRate ?? 1,
    });
}
