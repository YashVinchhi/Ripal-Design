module.exports = {
  content: [
    "./**/*.php",
    "./public/**/*.php",
    "./assets/js/**/*.js",
    "./app/**/*.php"
  ],
  theme: {
    container: {
      center: true,
      padding: {
        DEFAULT: '1rem',
        sm: '1rem',
        lg: '2rem',
        xl: '2rem',
        '2xl': '2rem',
      },
      screens: {
        sm: '640px',
        md: '768px',
        lg: '1024px',
        xl: '1280px',
        '2xl': '1536px',
      },
    },
    extend: {
      spacing: {
        xs: '0.25rem',
        sm: '0.5rem',
        md: '0.75rem',
        lg: '1rem',
        xl: '1.5rem',
        '2xl': '2rem',
        '3xl': '3rem',
      },
      maxWidth: {
        content: '1280px',
      },
      colors: {
        'rajkot-rust': '#94180C',
        'canvas-white': '#F9FAFB',
        'foundation-grey': '#2D2D2D',
        'slate-accent': '#334155',
        'approval-green': '#15803D',
        'pending-amber': '#B45309',
        'background-light': '#F9FAFB',
        'background-dark': '#121212'
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        serif: ['Playfair Display', 'serif']
      }
    }
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography')
  ]
};
