/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './app/Views/**/*.php',
    './public/**/*.html',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#475C7A',
          50: '#f3f6fa',
          100: '#e3eaf3',
          200: '#c7d4e6',
          300: '#9bb1cd',
          400: '#6885ad',
          500: '#475C7A',
          600: '#3b4d66',
          700: '#314056',
          800: '#293648',
          900: '#222c3b',
        },
        accent: {
          DEFAULT: '#14B8A6',
          50: '#effdfa',
          100: '#cffaf3',
          200: '#a0f3e7',
          300: '#67e6d6',
          400: '#34d2c0',
          500: '#14B8A6',
          600: '#0d9488',
          700: '#0f766e',
          800: '#115e59',
          900: '#134e4a',
        },
        forest: {
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a',
          700: '#15803d',
          800: '#166534',
          900: '#14532d',
          950: '#052e16',
        },
        ink: '#0F172A',
        cloud: '#F9FAFB',
      },
      fontFamily: {
        sans: ['Kanit', 'Sarabun', 'Inter', 'ui-sans-serif', 'system-ui'],
      },
      boxShadow: {
        soft: '0 4px 24px rgba(15,23,42,.06)',
      },
      gridTemplateColumns: {
        14: 'repeat(14, minmax(0, 1fr))',
      },
    },
  },
  // คลาสประเภท bg-<?= $c ?>-100 + text-<?= $c ?>-700 ไม่ถูก JIT เห็นเป็นลำดับต่อเนื่องจากมีแท็ก PHP คั่น — กันเลย์เอาต์หลังบ้านพังในบางหน้า
  safelist: [
    {
      pattern:
        /^(bg|text)-(amber|emerald|rose|slate|blue|indigo|purple|teal|yellow|orange|sky|cyan|violet|red|lime|green|neutral|pink|fuchsia)-(50|100|200|600|700|800|900)$/,
    },
  ],
  plugins: [],
};
