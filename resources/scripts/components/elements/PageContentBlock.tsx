import React, { useEffect } from 'react';
import ContentContainer from '@/components/elements/ContentContainer';
import { CSSTransition } from 'react-transition-group';
import tw from 'twin.macro';
import FlashMessageRender from '@/components/FlashMessageRender';
import { useStoreState } from 'easy-peasy';

export interface PageContentBlockProps {
    title?: string;
    className?: string;
    showFlashKey?: string;
}

const PageContentBlock: React.FC<PageContentBlockProps> = ({ title, showFlashKey, className, children }) => {
    const branding = useStoreState((state) => state.settings.data?.branding);
    const name = useStoreState((state) => state.settings.data?.name) || 'PandaScript';
    const company = branding?.company || name;
    const companyUrl = branding?.url || 'https://pandascript.dev';
    const links = branding?.links?.filter((link) => link.label && link.url) || [];

    useEffect(() => {
        if (title) {
            document.title = title;
        }
    }, [title]);

    return (
        <CSSTransition timeout={150} classNames={'fade'} appear in>
            <>
                <ContentContainer css={tw`my-4 sm:my-10`} className={className}>
                    {showFlashKey && <FlashMessageRender byKey={showFlashKey} css={tw`mb-4`} />}
                    {children}
                </ContentContainer>
                <ContentContainer css={tw`mb-4`}>
                    <div css={tw`text-center text-neutral-500 text-xs`}>
                        {links.length > 0 && (
                            <p css={tw`m-0`}>
                                {links.map((link, index) => (
                                    <React.Fragment key={link.url + link.label}>
                                        {index > 0 && <span css={tw`mx-2 text-neutral-600`}>·</span>}
                                        <a
                                            rel={'noopener nofollow noreferrer'}
                                            href={link.url}
                                            target={'_blank'}
                                            css={tw`no-underline text-neutral-500 hover:text-neutral-300`}
                                        >
                                            {link.label}
                                        </a>
                                    </React.Fragment>
                                ))}
                            </p>
                        )}
                        <p css={[tw`m-0`, links.length > 0 && tw`mt-2`]}>
                            <a
                                rel={'noopener nofollow noreferrer'}
                                href={companyUrl}
                                target={'_blank'}
                                css={tw`no-underline text-neutral-500 hover:text-neutral-300`}
                            >
                                {company}
                            </a>
                            &nbsp;&copy; {new Date().getFullYear()}
                        </p>
                    </div>
                </ContentContainer>
            </>
        </CSSTransition>
    );
};

export default PageContentBlock;
