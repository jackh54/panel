import React, { useState } from 'react';
import tw from 'twin.macro';
import { useStoreState } from 'easy-peasy';
import ContentContainer from '@/components/elements/ContentContainer';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faExclamationTriangle, faInfoCircle, faTimes } from '@fortawesome/free-solid-svg-icons';

const STORAGE_PREFIX = 'panel:announcement:dismissed:';

const typeStyles = {
    info: tw`bg-blue-600/20 border-blue-500/40 text-blue-100`,
    warning: tw`bg-yellow-600/20 border-yellow-500/40 text-yellow-100`,
    danger: tw`bg-red-600/20 border-red-500/40 text-red-100`,
};

export default () => {
    const announcement = useStoreState((state) => state.settings.data?.announcement);
    const message = announcement?.message?.trim() || '';
    const type = announcement?.type || 'info';
    const enabled = Boolean(announcement?.enabled && message);

    const [dismissed, setDismissed] = useState(() => {
        if (!enabled || typeof window === 'undefined') {
            return false;
        }
        try {
            return window.sessionStorage.getItem(STORAGE_PREFIX + message) === '1';
        } catch {
            return false;
        }
    });

    if (!enabled || dismissed) {
        return null;
    }

    const dismiss = () => {
        try {
            window.sessionStorage.setItem(STORAGE_PREFIX + message, '1');
        } catch {
            // ignore storage failures
        }
        setDismissed(true);
    };

    return (
        <ContentContainer css={tw`mt-4`}>
            <div
                css={[tw`flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-panel-sm`, typeStyles[type]]}
                role={'status'}
            >
                <FontAwesomeIcon
                    icon={type === 'info' ? faInfoCircle : faExclamationTriangle}
                    css={tw`mt-0.5 flex-none`}
                />
                <p css={tw`flex-1 m-0 whitespace-pre-wrap`}>{message}</p>
                <button
                    type={'button'}
                    onClick={dismiss}
                    css={tw`flex-none opacity-70 hover:opacity-100 p-0.5`}
                    aria-label={'Dismiss announcement'}
                >
                    <FontAwesomeIcon icon={faTimes} />
                </button>
            </div>
        </ContentContainer>
    );
};
