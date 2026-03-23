/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.ts',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                editor: {
                    bg: '#1e1e1e',
                    panel: '#252526',
                    border: '#3c3c3c',
                    text: '#cccccc',
                    muted: '#858585',
                    accent: '#0078d4',
                    hover: '#2a2d2e',
                    active: '#37373d',
                    input: '#3c3c3c',
                    danger: '#f14c4c',
                    success: '#4ec9b0',
                },
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
                mono: ['JetBrains Mono', 'Fira Code', 'monospace'],
            },
        },
    },
    plugins: [],
};
