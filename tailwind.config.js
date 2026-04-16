module.exports = {
  content: [
    "./**/*.php",
    "./public/**/*.php",
    "./assets/js/**/*.js",
    "./app/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        'rajkot-rust': '#94180C',
        'canvas-white': '#F9FAFB',
        'foundation-grey': '#2D2D2D',
        'slate-accent': '#334155',
        'approval-green': '#15803D',
        'pending-amber': '#B45309'
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
