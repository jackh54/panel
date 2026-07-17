import React, { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

export type TurnstileHandle = {
    execute: () => Promise<string>;
    reset: () => void;
};

type Props = {
    siteKey: string;
    onVerify: (token: string) => void;
    onExpire?: () => void;
    onError?: (error?: Error) => void;
};

type TurnstileApi = {
    render: (element: HTMLElement, options: Record<string, unknown>) => string;
    execute: (widgetId: string) => void;
    reset: (widgetId: string) => void;
    remove: (widgetId: string) => void;
};

declare global {
    interface Window {
        turnstile?: TurnstileApi;
        __turnstileOnLoad?: () => void;
    }
}

const SCRIPT_ID = 'cf-turnstile-script';
const SCRIPT_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=__turnstileOnLoad';

function loadTurnstileScript(): Promise<TurnstileApi> {
    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }

    return new Promise((resolve, reject) => {
        const existing = document.getElementById(SCRIPT_ID) as HTMLScriptElement | null;
        const previous = window.__turnstileOnLoad;

        window.__turnstileOnLoad = () => {
            previous?.();
            if (window.turnstile) {
                resolve(window.turnstile);
            } else {
                reject(new Error('Turnstile failed to load.'));
            }
        };

        if (existing) {
            return;
        }

        const script = document.createElement('script');
        script.id = SCRIPT_ID;
        script.src = SCRIPT_SRC;
        script.async = true;
        script.defer = true;
        script.onerror = () => reject(new Error('Failed to load Cloudflare Turnstile.'));
        document.head.appendChild(script);
    });
}

const TurnstileWidget = forwardRef<TurnstileHandle, Props>(({ siteKey, onVerify, onExpire, onError }, ref) => {
    const containerRef = useRef<HTMLDivElement>(null);
    const widgetIdRef = useRef<string | null>(null);
    const pendingRef = useRef<{
        resolve: (token: string) => void;
        reject: (error: Error) => void;
    } | null>(null);

    useEffect(() => {
        let removed = false;

        loadTurnstileScript()
            .then((api) => {
                if (removed || !containerRef.current || widgetIdRef.current) {
                    return;
                }

                widgetIdRef.current = api.render(containerRef.current, {
                    sitekey: siteKey,
                    size: 'invisible',
                    callback: (token: string) => {
                        onVerify(token);
                        pendingRef.current?.resolve(token);
                        pendingRef.current = null;
                    },
                    'expired-callback': () => {
                        onExpire?.();
                        pendingRef.current?.reject(new Error('Turnstile token expired.'));
                        pendingRef.current = null;
                    },
                    'error-callback': () => {
                        const error = new Error('Turnstile challenge failed.');
                        onError?.(error);
                        pendingRef.current?.reject(error);
                        pendingRef.current = null;
                    },
                });
            })
            .catch((error) => onError?.(error instanceof Error ? error : new Error(String(error))));

        return () => {
            removed = true;
            if (widgetIdRef.current && window.turnstile) {
                window.turnstile.remove(widgetIdRef.current);
                widgetIdRef.current = null;
            }
        };
    }, [siteKey]);

    useImperativeHandle(ref, () => ({
        execute: () =>
            new Promise<string>((resolve, reject) => {
                if (!widgetIdRef.current || !window.turnstile) {
                    reject(new Error('Turnstile is not ready yet.'));
                    return;
                }

                pendingRef.current = { resolve, reject };
                window.turnstile.execute(widgetIdRef.current);
            }),
        reset: () => {
            if (widgetIdRef.current && window.turnstile) {
                window.turnstile.reset(widgetIdRef.current);
            }
            pendingRef.current = null;
        },
    }));

    return <div ref={containerRef} style={{ display: 'none' }} />;
});

TurnstileWidget.displayName = 'TurnstileWidget';

export default TurnstileWidget;
