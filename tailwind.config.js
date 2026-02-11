/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './vendor/livewire/flux/stubs/**/*.blade.php',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        heading: ['Alan Sans', 'system-ui', 'sans-serif'],
        body: ['Fira Sans', 'system-ui', 'sans-serif'],
      },
      colors: {
        'hv-red': '#E84C4F',
        'hv-red-hover': '#D43E41',
        'hv-blue': '#4CB7C5',
        'hv-yellow': '#F4C44E',
        'hv-purple': '#B57BB3',
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
        'h1': ['3.5rem', { lineHeight: '1.25' }],     // 56px
        'h2': ['2.75rem', { lineHeight: '1.25' }],    // 44px
        'h3': ['2rem', { lineHeight: '1.25' }],       // 32px
        'h4': ['1.75rem', { lineHeight: '1.25' }],    // 28px
        'body': ['1.25rem', { lineHeight: '1.6' }],   // 20px
        'small': ['1.125rem', { lineHeight: '1.6' }], // 18px
        'tiny': ['1rem', { lineHeight: '1.6' }],      // 16px
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
  plugins: [],
}
