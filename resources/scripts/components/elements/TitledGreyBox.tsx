import React, { memo } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { IconProp } from '@fortawesome/fontawesome-svg-core';
import tw from 'twin.macro';
import isEqual from 'react-fast-compare';

interface Props {
    icon?: IconProp;
    title: string | React.ReactNode;
    className?: string;
    children: React.ReactNode;
}

const TitledGreyBox = ({ icon, title, children, className }: Props) => (
    <div
        css={tw`rounded-xl bg-neutral-700/90 border border-neutral-600/60 shadow-panel-sm overflow-hidden`}
        className={className}
    >
        <div css={tw`bg-neutral-800/80 px-4 py-3 border-b border-neutral-600/50`}>
            {typeof title === 'string' ? (
                <p css={tw`text-xs uppercase tracking-wide text-neutral-300`}>
                    {icon && <FontAwesomeIcon icon={icon} css={tw`mr-2 text-cyan-400`} />}
                    {title}
                </p>
            ) : (
                title
            )}
        </div>
        <div css={tw`p-4`}>{children}</div>
    </div>
);

export default memo(TitledGreyBox, isEqual);
