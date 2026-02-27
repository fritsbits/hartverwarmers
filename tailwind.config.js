/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './vendor/livewire/flux/stubs/**/*.blade.php',
  ],

  theme: {
    extend: {
      fontFamily: {
        heading: ['Aleo', 'system-ui', 'serif'],
        body: ['Fira Sans', 'system-ui', 'sans-serif'],
      },
      colors: {
        'hv-coral': '#E8764B',
        'hv-coral-hover': '#D4683F',
        'hv-blue': '#4CB7C5',
        'hv-yellow': '#F4C44E',
        'hv-purple': '#B57BB3',
        'hv-cream': '#FEF8F4',
        'hv-warm-gray': '#F5F0EC',
        'hv-border': '#EBE4DE',
        'hv-border-hover': '#DDD5CD',
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
        'sm': '6px',
        'md': '12px',
      },
      // Custom line heights
      lineHeight: {
        'tight': '1.25',
        'normal': '1.6',
        'loose': '1.8',
      },
      // Custom box shadows with warm tones
      boxShadow: {
        'card': '0 2px 8px rgba(60, 40, 20, 0.05)',
        'card-hover': '0 4px 16px rgba(60, 40, 20, 0.10)',
      },
    },
  },
  plugins: [],
}
