/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  // 'class' means the dark: variants only activate when something adds a
  // `.dark` class — which this app never does. Light mode only.
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Noto Sans Arabic', 'system-ui', 'sans-serif'],
      },
      colors: {
        // Vristo primary — vivid indigo-blue.
        brand: {
          50:  '#eef2ff',
          100: '#e0e7ff',
          200: '#c7d2fe',
          300: '#a5b4fc',
          400: '#818cf8',
          500: '#5a6ef0',
          600: '#4361ee',
          700: '#374cd1',
          800: '#2f3fa8',
          900: '#2b3a85',
        },
      },
      boxShadow: {
        // Layered, low-opacity shadows read as "lifted" without the muddy grey halo.
        card:       '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.06)',
        'card-hov': '0 4px 6px -1px rgb(15 23 42 / 0.07), 0 2px 4px -2px rgb(15 23 42 / 0.05)',
      },
      keyframes: {
        'fade-up': {
          '0%':   { opacity: '0', transform: 'translateY(6px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
      },
      animation: {
        'fade-up': 'fade-up 0.35s ease-out both',
      },
    },
  },
  plugins: [
    // tailwindcss-logical adds ps-*, pe-*, ms-*, me-*, start/end variants.
    require('tailwindcss-logical'),
    // Preflight resets inputs to border-width:0. Without this plugin the
    // `border-slate-300` classes only set a colour, so every field rendered
    // borderless. 'class' strategy = opt in via .form-input etc, which the
    // .field component class below applies.
    require('@tailwindcss/forms')({ strategy: 'class' }),
  ],
};
