/** Tailwind scans Vue templates while the custom stylesheet carries the ASTRA system. */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts}'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
      colors: {
        brand: {
          dark: '#0A0A0A',
          yellow: '#FFD100',
          hover: '#E5BC00',
          light: '#F8F9FA',
          gray: '#E9ECEF',
          muted: '#6C757D',
        },
        ink: '#101828',
        navy: '#061A44',
        electric: '#1264FF',
        cream: '#F7F3EC'
      }
    }
  },
  plugins: []
}
