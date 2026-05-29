/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      fontFamily: {
        // Bundled locally — see src/assets/fonts/fonts.css
        sans: ['Inter', 'system-ui', 'sans-serif'],
        ku:   ['Vazirmatn', 'Noto Sans Arabic', 'sans-serif'],
      },
      colors: {
        brand: {
          50:  '#f0f9ff',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
        },
      },
    },
  },
  // tailwindcss-logical adds ps-*, pe-*, ms-*, me-*, start/end variants.
  plugins: [require('tailwindcss-logical')],
};
