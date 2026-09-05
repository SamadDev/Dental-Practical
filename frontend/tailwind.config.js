/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        nunito: ['Inter', 'sans-serif'],
      },
      colors: {
        primary: {
          DEFAULT: '#4a5568',
          light: '#f7fafc',
          'dark-light': 'rgba(74,85,104,.15)',
        },
        secondary: {
          DEFAULT: '#718096',
          light: '#edf2f7',
          'dark-light': 'rgb(113 128 150 / 15%)',
        },
        success: {
          DEFAULT: '#48bb78',
          light: '#f0fff4',
          'dark-light': 'rgba(72,187,120,.15)',
        },
        danger: {
          DEFAULT: '#e53e3e',
          light: '#fff5f5',
          'dark-light': 'rgba(229,62,62,.15)',
        },
        warning: {
          DEFAULT: '#d69e2e',
          light: '#fffff0',
          'dark-light': 'rgba(214,158,46,.15)',
        },
        info: {
          DEFAULT: '#4299e1',
          light: '#ebf8ff',
          'dark-light': 'rgba(66,153,225,.15)',
        },
        dark: {
          DEFAULT: '#2d3748',
          light: '#e2e8f0',
          'dark-light': 'rgba(45,55,72,.15)',
        },
        black: {
          DEFAULT: '#1a202c',
          light: '#e2e8f0',
          'dark-light': 'rgba(26,32,44,.15)',
        },
        white: {
          DEFAULT: '#ffffff',
          light: '#e2e8f0',
          dark: '#718096',
        },
        'white-light': 'rgba(255, 255, 255, 0.4)',
        'white-dark': '#e2e8f0',
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
