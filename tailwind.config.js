const colors = require('tailwindcss/colors');

// Cool charcoal palette — deeper surfaces, softer midtones for a modern dark UI.
const gray = {
    50: 'hsl(220, 24%, 96%)',
    100: 'hsl(220, 18%, 90%)',
    200: 'hsl(220, 14%, 78%)',
    300: 'hsl(220, 12%, 62%)',
    400: 'hsl(220, 11%, 48%)',
    500: 'hsl(222, 12%, 36%)',
    600: 'hsl(223, 16%, 26%)',
    700: 'hsl(224, 20%, 18%)',
    800: 'hsl(225, 24%, 12%)',
    900: 'hsl(226, 28%, 8%)',
};

module.exports = {
    content: [
        './resources/scripts/**/*.{js,ts,tsx}',
    ],
    theme: {
        extend: {
            fontFamily: {
                header: ['"IBM Plex Sans"', '"Roboto"', 'system-ui', 'sans-serif'],
                sans: ['"IBM Plex Sans"', 'system-ui', 'sans-serif'],
            },
            colors: {
                black: '#070a0e',
                // "primary" and "neutral" are deprecated, prefer the use of "blue" and "gray"
                // in new code.
                primary: colors.sky,
                gray: gray,
                neutral: gray,
                cyan: colors.cyan,
                blue: colors.sky,
            },
            fontSize: {
                '2xs': '0.625rem',
            },
            transitionDuration: {
                250: '250ms',
            },
            transitionTimingFunction: {
                panel: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
            boxShadow: {
                panel: '0 1px 0 rgba(255,255,255,0.04) inset, 0 8px 24px rgba(0,0,0,0.35)',
                'panel-sm': '0 1px 0 rgba(255,255,255,0.03) inset, 0 2px 8px rgba(0,0,0,0.25)',
            },
            borderColor: theme => ({
                default: theme('colors.neutral.600', 'currentColor'),
            }),
        },
    },
    plugins: [
        require('@tailwindcss/line-clamp'),
        require('@tailwindcss/forms')({
            strategy: 'class',
        }),
    ]
};
