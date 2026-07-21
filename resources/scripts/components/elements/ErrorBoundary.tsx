import React from 'react';
import * as Sentry from '@sentry/react';
import tw from 'twin.macro';
import Icon from '@/components/elements/Icon';
import { faExclamationTriangle } from '@fortawesome/free-solid-svg-icons';

interface State {
    hasError: boolean;
}

// eslint-disable-next-line @typescript-eslint/ban-types
class ErrorBoundary extends React.Component<{}, State> {
    state: State = {
        hasError: false,
    };

    static getDerivedStateFromError() {
        return { hasError: true };
    }

    componentDidCatch(error: Error, info: React.ErrorInfo) {
        console.error(error);
        const eventId = Sentry.captureException(error, { extra: { componentStack: info.componentStack } });
        if (eventId) {
            Sentry.showReportDialog({ eventId });
        }
    }

    render() {
        return this.state.hasError ? (
            <div css={tw`flex items-center justify-center w-full my-4`}>
                <div
                    css={tw`flex items-center bg-neutral-800 border border-neutral-600/50 rounded-xl p-3 text-red-400 shadow-panel-sm`}
                >
                    <Icon icon={faExclamationTriangle} css={tw`h-4 w-auto mr-2`} />
                    <p css={tw`text-sm text-neutral-200`}>
                        An error was encountered by the application while rendering this view. Try refreshing the page.
                    </p>
                </div>
            </div>
        ) : (
            this.props.children
        );
    }
}

export default ErrorBoundary;
