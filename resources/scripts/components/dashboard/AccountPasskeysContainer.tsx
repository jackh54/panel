import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { format } from 'date-fns';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faKey, faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import PageContentBlock from '@/components/elements/PageContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import ContentBox from '@/components/elements/ContentBox';
import GreyRowBox from '@/components/elements/GreyRowBox';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Dialog } from '@/components/elements/dialog';
import Button from '@/components/elements/Button';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import Input from '@/components/elements/Input';
import { Field, Form, Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import { useFlashKey } from '@/plugins/useFlash';
import { createPasskey, deletePasskey, usePasskeys } from '@/api/account/passkeys';
import { Passkey } from '@definitions/user';

interface CreateValues {
    name: string;
}

export default () => {
    const { clearAndAddHttpError } = useFlashKey('account');
    const { data, error, isValidating, mutate } = usePasskeys({
        revalidateOnMount: true,
        revalidateOnFocus: false,
    });
    const [deleteTarget, setDeleteTarget] = useState<Passkey | null>(null);
    const [supported, setSupported] = useState(true);

    useEffect(() => {
        clearAndAddHttpError(error);
    }, [error]);

    useEffect(() => {
        setSupported(typeof window !== 'undefined' && !!window.PublicKeyCredential);
    }, []);

    const submit = (values: CreateValues, { setSubmitting, resetForm }: FormikHelpers<CreateValues>) => {
        clearAndAddHttpError();

        createPasskey(values.name)
            .then((passkey) => {
                resetForm();
                mutate((keys) => (keys || []).concat(passkey));
            })
            .catch((err) => clearAndAddHttpError(err))
            .then(() => setSubmitting(false));
    };

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        clearAndAddHttpError();
        const uuid = deleteTarget.uuid;

        Promise.all([mutate((keys) => keys?.filter((key) => key.uuid !== uuid), false), deletePasskey(uuid)])
            .catch((err) => {
                mutate(undefined, true).catch(console.error);
                clearAndAddHttpError(err);
            })
            .then(() => setDeleteTarget(null));
    };

    return (
        <PageContentBlock title={'Passkeys'}>
            <FlashMessageRender byKey={'account'} />
            <div css={tw`md:flex flex-nowrap my-10`}>
                <ContentBox title={'Add Passkey'} css={tw`flex-none w-full md:w-1/2`}>
                    {!supported ? (
                        <p css={tw`text-sm text-neutral-300`}>
                            Passkeys are not supported in this browser. Try a recent version of Chrome, Safari, Edge, or
                            Firefox.
                        </p>
                    ) : (
                        <Formik
                            onSubmit={submit}
                            initialValues={{ name: '' }}
                            validationSchema={object().shape({
                                name: string().required('A name is required.'),
                            })}
                        >
                            {({ isSubmitting }) => (
                                <Form>
                                    <SpinnerOverlay visible={isSubmitting} />
                                    <p css={tw`text-sm text-neutral-300 mb-4`}>
                                        Passkeys let you sign in with your device biometrics or a hardware security key
                                        — no password required.
                                    </p>
                                    <FormikFieldWrapper
                                        label={'Passkey Name'}
                                        name={'name'}
                                        description={'A label so you can recognize this device later.'}
                                        css={tw`mb-6`}
                                    >
                                        <Field name={'name'} as={Input} placeholder={'e.g. MacBook Touch ID'} />
                                    </FormikFieldWrapper>
                                    <div css={tw`flex justify-end mt-6`}>
                                        <Button type={'submit'} disabled={isSubmitting}>
                                            Register Passkey
                                        </Button>
                                    </div>
                                </Form>
                            )}
                        </Formik>
                    )}
                </ContentBox>
                <ContentBox title={'Your Passkeys'} css={tw`flex-1 overflow-hidden mt-8 md:mt-0 md:ml-8`}>
                    <SpinnerOverlay visible={!data && isValidating} />
                    <Dialog.Confirm
                        open={!!deleteTarget}
                        title={'Delete Passkey'}
                        confirm={'Delete Passkey'}
                        onConfirmed={confirmDelete}
                        onClose={() => setDeleteTarget(null)}
                    >
                        Removing this passkey will prevent it from being used to sign in to your account.
                    </Dialog.Confirm>
                    {!data || data.length === 0 ? (
                        <p css={tw`text-center text-sm`}>
                            {!data ? 'Loading...' : 'No passkeys have been registered for this account.'}
                        </p>
                    ) : (
                        data.map((passkey, index) => (
                            <GreyRowBox
                                key={passkey.uuid}
                                css={[tw`bg-neutral-600 flex items-center`, index > 0 && tw`mt-2`]}
                            >
                                <FontAwesomeIcon icon={faKey} css={tw`text-neutral-300`} />
                                <div css={tw`ml-4 flex-1 overflow-hidden`}>
                                    <p css={tw`text-sm break-words font-medium`}>{passkey.name}</p>
                                    <p css={tw`text-xs text-neutral-300 mt-1`}>
                                        Added {format(passkey.createdAt, 'MMM do, yyyy HH:mm')}
                                        {passkey.lastUsedAt
                                            ? ` · Last used ${format(passkey.lastUsedAt, 'MMM do, yyyy HH:mm')}`
                                            : ''}
                                    </p>
                                </div>
                                <button css={tw`ml-4 p-2 text-sm`} onClick={() => setDeleteTarget(passkey)}>
                                    <FontAwesomeIcon
                                        icon={faTrashAlt}
                                        css={tw`text-neutral-400 hover:text-red-400 transition-colors duration-150`}
                                    />
                                </button>
                            </GreyRowBox>
                        ))
                    )}
                </ContentBox>
            </div>
        </PageContentBlock>
    );
};
