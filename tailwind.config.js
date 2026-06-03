/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                primary: '#0f3460',
                accent: '#16c79a',
                aside: '#0d2847',
            },
            fontFamily: {
                sans: ['Space Grotesk', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                mono: ['Space Mono', 'ui-monospace', 'monospace'],
            }
        },
    },
    plugins: [],
}
