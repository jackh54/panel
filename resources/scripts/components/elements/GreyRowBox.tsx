import styled from 'styled-components/macro';
import tw from 'twin.macro';

export default styled.div<{ $hoverable?: boolean }>`
    ${tw`flex rounded-xl no-underline text-neutral-200 items-center bg-neutral-700/90 p-4 border border-neutral-600/40 transition-all duration-200 overflow-hidden shadow-panel-sm`};
    transition-timing-function: cubic-bezier(0.22, 1, 0.36, 1);

    ${(props) => props.$hoverable !== false && tw`hover:border-neutral-500/70 hover:bg-neutral-700 hover:shadow-panel`};

    & .icon {
        ${tw`rounded-lg w-14 h-14 flex items-center justify-center bg-neutral-600/80 p-3 text-cyan-300`};
    }
`;
