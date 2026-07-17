import * as React from 'react';
import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import requestPasswordResetEmail from '@/api/auth/requestPasswordResetEmail';
import { httpErrorToHuman } from '@/api/http';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { useStoreState } from 'easy-peasy';
import Field from '@/components/elements/Field';
import { Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import TurnstileWidget, { TurnstileHandle } from '@/components/elements/TurnstileWidget';
import useFlash from '@/plugins/useFlash';

interface Values {
    email: string;
}

export default () => {
    const ref = useRef<TurnstileHandle>(null);
    const [token, setToken] = useState('');

    const { clearFlashes, addFlash } = useFlash();
    const captcha = useStoreState((state) => state.settings.data!.turnstile || state.settings.data!.recaptcha);
    const captchaEnabled = captcha.enabled;
    const siteKey = captcha.siteKey;

    useEffect(() => {
        clearFlashes();
    }, []);

    const handleSubmission = ({ email }: Values, { setSubmitting, resetForm }: FormikHelpers<Values>) => {
        clearFlashes();

        if (captchaEnabled && !token) {
            ref.current!.execute().catch((error) => {
                console.error(error);

                setSubmitting(false);
                addFlash({ type: 'error', title: 'Error', message: httpErrorToHuman(error) });
            });

            return;
        }

        requestPasswordResetEmail(email, token)
            .then((response) => {
                resetForm();
                addFlash({ type: 'success', title: 'Success', message: response });
            })
            .catch((error) => {
                console.error(error);
                addFlash({ type: 'error', title: 'Error', message: httpErrorToHuman(error) });
            })
            .then(() => {
                setToken('');
                ref.current?.reset();

                setSubmitting(false);
            });
    };

    return (
        <Formik
            onSubmit={handleSubmission}
            initialValues={{ email: '' }}
            validationSchema={object().shape({
                email: string()
                    .email('A valid email address must be provided to continue.')
                    .required('A valid email address must be provided to continue.'),
            })}
        >
            {({ isSubmitting, setSubmitting, submitForm }) => (
                <LoginFormContainer title={'Reset your password'}>
                    <Field
                        label={'Email'}
                        description={
                            'Enter your account email address to receive instructions on resetting your password.'
                        }
                        name={'email'}
                        type={'email'}
                    />
                    <div css={tw`mt-6`}>
                        <Button type={'submit'} size={'xlarge'} disabled={isSubmitting} isLoading={isSubmitting}>
                            Send Email
                        </Button>
                    </div>
                    {captchaEnabled && (
                        <TurnstileWidget
                            ref={ref}
                            siteKey={siteKey || '_invalid_key'}
                            onVerify={(response) => {
                                setToken(response);
                                submitForm();
                            }}
                            onExpire={() => {
                                setSubmitting(false);
                                setToken('');
                            }}
                            onError={() => {
                                setSubmitting(false);
                                setToken('');
                            }}
                        />
                    )}
                    <div css={tw`mt-5 text-center`}>
                        <Link
                            to={'/auth/login'}
                            css={tw`text-xs text-neutral-400 tracking-wide no-underline hover:text-neutral-200`}
                        >
                            Return to login
                        </Link>
                    </div>
                </LoginFormContainer>
            )}
        </Formik>
    );
};
