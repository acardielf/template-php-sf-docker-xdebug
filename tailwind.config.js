/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './templates/**/*.{html,twig}',
        './assets/**/*.js',
        './src/Infrastructure/Controller/**/*.php',
    ],
    theme: {
        extend: {},
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};
