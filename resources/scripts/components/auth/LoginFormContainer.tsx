import React, { forwardRef } from 'react';
import { Form } from 'formik';
import styled from 'styled-components/macro';
import { breakpoint } from '@/theme';
import FlashMessageRender from '@/components/FlashMessageRender';
import tw from 'twin.macro';
import { useStoreState } from 'easy-peasy';

type Props = React.DetailedHTMLProps<React.FormHTMLAttributes<HTMLFormElement>, HTMLFormElement> & {
    title?: string;
};

const Shell = styled.div`
    ${tw`w-full px-4 mx-auto`};
    max-width: 420px;

    ${breakpoint('md')`
        ${tw`px-0`};
    `};
`;

const LoginFormContainer = forwardRef<HTMLFormElement, Props>(({ title, children, ...props }, ref) => {
    const branding = useStoreState((state) => state.settings.data?.branding);
    const name = useStoreState((state) => state.settings.data?.name) || 'PandaScript';
    const logo = branding?.logo || '/branding/logo.png';
    const company = branding?.company || name;
    const companyUrl = branding?.url || 'https://pandascript.dev';

    return (
        <Shell>
            <div css={tw`flex flex-col items-center text-center mb-8`}>
                <img src={logo} alt={''} css={tw`block w-20 h-20 object-contain mb-5`} />
                <h1 css={tw`text-3xl md:text-4xl font-header font-medium text-neutral-50 tracking-tight m-0`}>
                    {name}
                </h1>
                {title && (
                    <p css={tw`mt-2 mb-0 text-sm text-neutral-400 tracking-wide`}>{title}</p>
                )}
            </div>

            <FlashMessageRender css={tw`mb-4`} />

            <Form {...props} ref={ref}>
                <div
                    css={tw`w-full bg-neutral-700 border border-neutral-600 shadow-panel rounded-xl p-6 md:p-8`}
                >
                    {children}
                </div>
            </Form>

            <p css={tw`text-center text-neutral-500 text-xs mt-6 mb-0`}>
                &copy; {new Date().getFullYear()}{' '}
                <a
                    rel={'noopener nofollow noreferrer'}
                    href={companyUrl}
                    target={'_blank'}
                    css={tw`no-underline text-neutral-500 hover:text-neutral-300`}
                >
                    {company}
                </a>
            </p>
        </Shell>
    );
});

LoginFormContainer.displayName = 'LoginFormContainer';

export default LoginFormContainer;
