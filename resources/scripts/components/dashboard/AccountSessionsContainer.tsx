import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { format } from 'date-fns';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faDesktop, faMobileAlt, faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import PageContentBlock from '@/components/elements/PageContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import ContentBox from '@/components/elements/ContentBox';
import GreyRowBox from '@/components/elements/GreyRowBox';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Dialog } from '@/components/elements/dialog';
import { Button } from '@/components/elements/button/index';
import { useFlashKey } from '@/plugins/useFlash';
import {
    describeUserAgent,
    revokeOtherUserSessions,
    revokeUserSession,
    useUserSessions,
    UserSession,
} from '@/api/account/sessions';

const isMobileAgent = (userAgent: string | null): boolean =>
    !!userAgent && /Android|iPhone|iPad|iPod|Mobile/i.test(userAgent);

export default () => {
    const { clearAndAddHttpError } = useFlashKey('account');
    const { data, error, isValidating, mutate } = useUserSessions({
        revalidateOnMount: true,
        revalidateOnFocus: false,
    });
    const [revokeTarget, setRevokeTarget] = useState<UserSession | null>(null);
    const [revokeOthersOpen, setRevokeOthersOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        clearAndAddHttpError(error);
    }, [error]);

    const doRevoke = (session: UserSession) => {
        setSubmitting(true);
        clearAndAddHttpError();

        revokeUserSession(session.uuid)
            .then(() => mutate())
            .catch((err) => clearAndAddHttpError(err))
            .then(() => {
                setSubmitting(false);
                setRevokeTarget(null);
            });
    };

    const doRevokeOthers = () => {
        setSubmitting(true);
        clearAndAddHttpError();

        revokeOtherUserSessions()
            .then(() => mutate())
            .catch((err) => clearAndAddHttpError(err))
            .then(() => {
                setSubmitting(false);
                setRevokeOthersOpen(false);
            });
    };

    const otherSessions = (data || []).filter((session) => !session.isCurrent);

    return (
        <PageContentBlock title={'Devices'}>
            <FlashMessageRender byKey={'account'} />
            <div css={tw`md:flex flex-nowrap my-10`}>
                <ContentBox title={'Signed-in Devices'} css={tw`flex-1 overflow-hidden`}>
                    <SpinnerOverlay visible={(!data && isValidating) || submitting} />
                    <p css={tw`text-sm text-neutral-300 mb-4`}>
                        These are the browsers and devices currently signed in to your account. Revoke any you do not
                        recognize.
                    </p>
                    <Dialog.Confirm
                        title={'Revoke Device'}
                        confirm={'Revoke Session'}
                        open={!!revokeTarget}
                        onClose={() => setRevokeTarget(null)}
                        onConfirmed={() => revokeTarget && doRevoke(revokeTarget)}
                    >
                        This will sign out{' '}
                        <span css={tw`font-semibold`}>{describeUserAgent(revokeTarget?.userAgent || null)}</span>
                        {revokeTarget?.ipAddress ? ` (${revokeTarget.ipAddress})` : ''}. That device will need to sign
                        in again.
                    </Dialog.Confirm>
                    <Dialog.Confirm
                        title={'Sign Out Other Devices'}
                        confirm={'Sign Out Others'}
                        open={revokeOthersOpen}
                        onClose={() => setRevokeOthersOpen(false)}
                        onConfirmed={doRevokeOthers}
                    >
                        This will sign out every other browser and device using your account. Your current session will
                        stay signed in.
                    </Dialog.Confirm>
                    {!data || data.length === 0 ? (
                        <p css={tw`text-center text-sm`}>
                            {!data ? 'Loading...' : 'No active device sessions were found for this account.'}
                        </p>
                    ) : (
                        data.map((session, index) => (
                            <GreyRowBox
                                key={session.uuid}
                                css={[tw`bg-neutral-600 flex items-center`, index > 0 && tw`mt-2`]}
                            >
                                <FontAwesomeIcon
                                    icon={isMobileAgent(session.userAgent) ? faMobileAlt : faDesktop}
                                    css={tw`text-neutral-300`}
                                />
                                <div css={tw`ml-4 flex-1 overflow-hidden`}>
                                    <p css={tw`text-sm break-words font-medium`}>
                                        {describeUserAgent(session.userAgent)}
                                        {session.isCurrent && (
                                            <span
                                                css={tw`ml-2 text-2xs uppercase tracking-wide text-green-400 font-semibold`}
                                            >
                                                This device
                                            </span>
                                        )}
                                    </p>
                                    <p css={tw`text-xs text-neutral-300 mt-1`}>
                                        {session.ipAddress || 'Unknown IP'}
                                        {' · '}
                                        Last active {format(session.lastUsedAt, 'MMM do, yyyy HH:mm')}
                                    </p>
                                </div>
                                {!session.isCurrent && (
                                    <button css={tw`ml-4 p-2 text-sm`} onClick={() => setRevokeTarget(session)}>
                                        <FontAwesomeIcon
                                            icon={faTrashAlt}
                                            css={tw`text-neutral-400 hover:text-red-400 transition-colors duration-150`}
                                        />
                                    </button>
                                )}
                            </GreyRowBox>
                        ))
                    )}
                    {otherSessions.length > 0 && (
                        <div css={tw`mt-6`}>
                            <Button.Text onClick={() => setRevokeOthersOpen(true)}>
                                Sign out of {otherSessions.length} other device
                                {otherSessions.length === 1 ? '' : 's'}
                            </Button.Text>
                        </div>
                    )}
                </ContentBox>
            </div>
        </PageContentBlock>
    );
};
