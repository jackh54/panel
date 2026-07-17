import tw from 'twin.macro';
import { createGlobalStyle } from 'styled-components/macro';
// @ts-expect-error untyped font file
import font from '@fontsource-variable/ibm-plex-sans/files/ibm-plex-sans-latin-wght-normal.woff2';

export default createGlobalStyle`
    @font-face {
        font-family: 'IBM Plex Sans';
        font-style: normal;
        font-display: swap;
        font-weight: 100 700;
        src: url(${font}) format('woff2-variations');
        unicode-range: U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD;
    }

    html {
        color-scheme: dark;
    }

    body {
        ${tw`font-sans bg-neutral-800 text-neutral-200 antialiased`};
        letter-spacing: 0.01em;
        background-image:
            radial-gradient(ellipse 120% 80% at 50% -30%, rgba(56, 189, 248, 0.08), transparent 55%),
            radial-gradient(ellipse 80% 50% at 100% 0%, rgba(34, 211, 238, 0.04), transparent 40%);
        background-attachment: fixed;
        min-height: 100vh;
    }

    h1, h2, h3, h4, h5, h6 {
        ${tw`font-medium tracking-tight font-header text-neutral-100`};
    }

    p {
        ${tw`text-neutral-300 leading-relaxed font-sans`};
    }

    a {
        transition: color 200ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    form {
        ${tw`m-0`};
    }

    textarea, select, input, button, button:focus, button:focus-visible {
        ${tw`outline-none`};
    }

    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none !important;
        margin: 0;
    }

    input[type=number] {
        -moz-appearance: textfield !important;
    }

    ::selection {
        background: rgba(56, 189, 248, 0.35);
        color: #f8fafc;
    }

    /* Smooth page transitions used by CSSTransition */
    .fade-enter {
        opacity: 0;
    }
    .fade-enter-active {
        opacity: 1;
        transition: opacity 200ms cubic-bezier(0.22, 1, 0.36, 1);
    }
    .fade-exit {
        opacity: 1;
    }
    .fade-exit-active {
        opacity: 0;
        transition: opacity 150ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    /* Scroll Bar Style */
    ::-webkit-scrollbar {
        background: transparent;
        width: 12px;
        height: 12px;
    }

    ::-webkit-scrollbar-thumb {
        background: hsl(222, 12%, 28%);
        border: 3px solid transparent;
        background-clip: padding-box;
        border-radius: 999px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: hsl(220, 11%, 40%);
        border: 3px solid transparent;
        background-clip: padding-box;
    }

    ::-webkit-scrollbar-track-piece {
        margin: 4px 0;
    }

    ::-webkit-scrollbar-corner {
        background: transparent;
    }
`;
