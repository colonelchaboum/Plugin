module.exports = {
  content: ['./src/**/*.{js,jsx}'],
  // important: true strategy to avoid theme conflicts
  important: '#beer-supa-widgets-wrapper',
  theme: {
    extend: {
      colors: {
        'beer-gold': '#F28E1C',
        'beer-foam': '#F3F4F6',
        'beer-dark': '#1F2937',
      },
    },
  },
  plugins: [],
}
