const defaultTheme = require('tailwindcss/defaultTheme');
const forms = require('@tailwindcss/forms');

/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: ['class', '[data-theme="dark"]'],
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './Modules/**/resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"DM Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            fontSize: {
                'motor-xs': ['0.6875rem', { lineHeight: '1rem' }],
                'motor-sm': ['0.8125rem', { lineHeight: '1.25rem' }],
                'motor-base': ['0.9375rem', { lineHeight: '1.5rem' }],
                'motor-lg': ['1.0625rem', { lineHeight: '1.625rem' }],
                'motor-xl': ['1.25rem', { lineHeight: '1.75rem' }],
                'motor-display': ['1.875rem', { lineHeight: '2.25rem' }],
            },
            colors: {
                motor: {
                    canvas: 'rgb(var(--motor-canvas) / <alpha-value>)',
                    elevated: 'rgb(var(--motor-elevated) / <alpha-value>)',
                    muted: 'rgb(var(--motor-muted) / <alpha-value>)',
                    subtle: 'rgb(var(--motor-subtle) / <alpha-value>)',
                    border: 'rgb(var(--motor-border) / <alpha-value>)',
                    ring: 'rgb(var(--motor-ring) / <alpha-value>)',
                    ink: 'rgb(var(--motor-ink) / <alpha-value>)',
                    accent: 'rgb(var(--motor-accent) / <alpha-value>)',
                    accent2: 'rgb(var(--motor-accent-2) / <alpha-value>)',
                    success: 'rgb(var(--motor-success) / <alpha-value>)',
                    warning: 'rgb(var(--motor-warning) / <alpha-value>)',
                    danger: 'rgb(var(--motor-danger) / <alpha-value>)',
                },
            },
            boxShadow: {
                motor:
                    '0 1px 0 rgba(15, 23, 42, 0.04), 0 10px 30px rgba(15, 23, 42, 0.06)',
                'motor-lg':
                    '0 1px 0 rgba(15, 23, 42, 0.05), 0 28px 60px rgba(15, 23, 42, 0.12)',
                'motor-dark':
                    '0 1px 0 rgba(255, 255, 255, 0.04), 0 24px 50px rgba(0, 0, 0, 0.45)',
            },
            borderRadius: {
                motor: '0.875rem',
            },
            transitionTimingFunction: {
                'motor-out': 'cubic-bezier(0.16, 1, 0.3, 1)',
            },
        },
    },
    plugins: [forms],
};
