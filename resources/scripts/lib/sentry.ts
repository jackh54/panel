import * as Sentry from '@sentry/react';
import { SiteSettings } from '@/state/settings';

interface ExtendedWindow extends Window {
    SiteConfiguration?: SiteSettings;
}

/**
 * Initialize browser Sentry when a frontend DSN is configured.
 * Safe to call when DSN is missing — it becomes a no-op.
 */
export default function initSentry(): void {
    const sentry = (window as ExtendedWindow).SiteConfiguration?.sentry;
    if (!sentry?.dsn) {
        return;
    }

    Sentry.init({
        dsn: sentry.dsn,
        environment: sentry.environment || undefined,
        release: sentry.release || undefined,
        integrations: [Sentry.browserTracingIntegration(), Sentry.replayIntegration()],
        tracesSampleRate: sentry.tracesSampleRate ?? 0.1,
        replaysSessionSampleRate: sentry.replaysSessionSampleRate ?? 0,
        replaysOnErrorSampleRate: sentry.replaysOnErrorSampleRate ?? 1,
    });
}
