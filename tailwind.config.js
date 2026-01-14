import daisyui from 'daisyui'

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        heading: ['Roboto Slab', 'Georgia', 'serif'],
        body: ['Fira Sans', 'system-ui', 'sans-serif'],
      },
      colors: {
        'hv-red': '#E84C4F',
        'hv-red-hover': '#D43E41',
      },
      // Custom spacing scale per design guidelines
      spacing: {
        'xs': '0.25rem',   // 4px
        'sm': '0.5rem',    // 8px
        'md': '1rem',      // 16px
        'lg': '1.5rem',    // 24px
        'xl': '2rem',      // 32px
        '2xl': '3rem',     // 48px
        '3xl': '4rem',     // 64px
        '4xl': '6rem',     // 96px
      },
      // Custom font sizes per design guidelines
      fontSize: {
        'h1': ['2.5rem', { lineHeight: '1.25' }],    // 40px
        'h2': ['2rem', { lineHeight: '1.25' }],      // 32px
        'h3': ['1.5rem', { lineHeight: '1.25' }],    // 24px
        'h4': ['1.25rem', { lineHeight: '1.25' }],   // 20px
        'body': ['1rem', { lineHeight: '1.6' }],     // 16px
        'small': ['0.875rem', { lineHeight: '1.6' }], // 14px
        'tiny': ['0.75rem', { lineHeight: '1.6' }],  // 12px
      },
      // Custom max-widths
      maxWidth: {
        'intro': '800px',
      },
      // Custom border radius
      borderRadius: {
        'sm': '4px',
        'md': '8px',
      },
      // Custom line heights
      lineHeight: {
        'tight': '1.25',
        'normal': '1.6',
        'loose': '1.8',
      },
    },
  },
  plugins: [daisyui],
  daisyui: {
    themes: [
      {
        hartverwarmers: {
          'primary': '#E84C4F',
          'primary-content': '#ffffff',
          'secondary': '#4CB7C5',
          'secondary-content': '#ffffff',
          'accent': '#F4C44E',
          'accent-content': '#1F1F1F',
          'neutral': '#1F1F1F',
          'neutral-content': '#ffffff',
          'base-100': '#ffffff',
          'base-200': '#F5F6F7',
          'base-300': '#E8E8E8',
          'base-content': '#1F1F1F',
          'info': '#4CB7C5',
          'success': '#36D399',
          'warning': '#F4C44E',
          'error': '#E84C4F',
        },
      },
    ],
  },
}
