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

const Container = styled.div`
    ${breakpoint('sm')`
        ${tw`w-4/5 mx-auto`}
    `};

    ${breakpoint('md')`
        ${tw`p-10`}
    `};

    ${breakpoint('lg')`
        ${tw`w-3/5`}
    `};

    ${breakpoint('xl')`
        ${tw`w-full`}
        max-width: 700px;
    `};
`;

const LoginFormContainer = forwardRef<HTMLFormElement, Props>(({ title, ...props }, ref) => {
    const branding = useStoreState((state) => state.settings.data?.branding);
    const name = useStoreState((state) => state.settings.data?.name) || 'PandaScript';
    const logo = branding?.logo || '/assets/branding/logo.png';
    const company = branding?.company || name;
    const companyUrl = branding?.url || 'https://pandascript.dev';

    return (
        <Container>
            {title && <h2 css={tw`text-3xl text-center text-neutral-50 font-medium py-4 tracking-tight`}>{title}</h2>}
            <FlashMessageRender css={tw`mb-2 px-1`} />
            <Form {...props} ref={ref}>
                <div
                    css={tw`md:flex w-full bg-neutral-700/90 border border-neutral-600/60 shadow-panel rounded-2xl p-6 md:pl-0 mx-1 backdrop-blur-sm`}
                >
                    <div css={tw`flex-none select-none mb-6 md:mb-0 self-center`}>
                        <img src={logo} alt={name} css={tw`block w-40 md:w-48 mx-auto opacity-95`} />
                    </div>
                    <div css={tw`flex-1`}>{props.children}</div>
                </div>
            </Form>
            <p css={tw`text-center text-neutral-500 text-xs mt-4`}>
                &copy; {new Date().getFullYear()}&nbsp;
                <a
                    rel={'noopener nofollow noreferrer'}
                    href={companyUrl}
                    target={'_blank'}
                    css={tw`no-underline text-neutral-500 hover:text-neutral-300`}
                >
                    {company}
                </a>
            </p>
        </Container>
    );
});

export default LoginFormContainer;
