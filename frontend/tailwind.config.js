/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        nunito: ['Inter', 'sans-serif'],
      },
      colors: {
        primary: {
          DEFAULT: '#4361ee',
          light: '#eef2ff',
          'dark-light': 'rgba(67,97,238,.15)',
        },
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
        secondary: {
          DEFAULT: '#8b5cf6',
          light: '#f3e8ff',
          'dark-light': 'rgb(139 92 246 / 15%)',
        },
        success: {
          DEFAULT: '#00ab55',
          light: '#ddf5f0',
          'dark-light': 'rgba(0,171,85,.15)',
        },
        danger: {
          DEFAULT: '#e7515a',
          light: '#fff5f5',
          'dark-light': 'rgba(231,81,90,.15)',
        },
        warning: {
          DEFAULT: '#e2a03f',
          light: '#fff9ed',
          'dark-light': 'rgba(226,160,63,.15)',
        },
        info: {
          DEFAULT: '#2196f3',
          light: '#e7f7ff',
          'dark-light': 'rgba(33,150,243,.15)',
        },
        dark: {
          DEFAULT: '#3b3f5c',
          light: '#eaeaec',
          'dark-light': 'rgba(59,63,92,.15)',
        },
        black: {
          DEFAULT: '#0e1726',
          light: '#e3e4eb',
          'dark-light': 'rgba(14,23,38,.15)',
        },
        white: {
          DEFAULT: '#ffffff',
          light: '#e0e6ed',
          dark: '#888ea8',
        },
      },
      boxShadow: {
        'card': '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.06)',
        'card-hov': '0 4px 6px -1px rgb(15 23 42 / 0.07), 0 2px 4px -2px rgb(15 23 42 / 0.05)',
        '3xl': '0 2px 2px rgb(224 230 237 / 46%), 1px 6px 7px rgb(224 230 237 / 46%)',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms')({ strategy: 'class' }),
  ],
};
