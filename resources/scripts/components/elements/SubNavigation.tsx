import styled from 'styled-components/macro';
import tw, { theme } from 'twin.macro';

const SubNavigation = styled.div`
    ${tw`w-full bg-neutral-800/80 border-b border-neutral-700/70 overflow-x-auto`};
    backdrop-filter: blur(8px);

    & > div {
        ${tw`flex items-center text-sm mx-auto px-2`};
        max-width: 1200px;

        & > a,
        & > div {
            ${tw`inline-block py-3 px-4 text-neutral-400 no-underline whitespace-nowrap transition-all duration-200`};
            transition-timing-function: cubic-bezier(0.22, 1, 0.36, 1);

            &:not(:first-of-type) {
                ${tw`ml-2`};
            }

            &:hover {
                ${tw`text-neutral-100`};
            }

            &:active,
            &.active {
                ${tw`text-neutral-50`};
                box-shadow: inset 0 -2px ${theme`colors.cyan.400`.toString()};
            }
        }
    }
`;

export default SubNavigation;
