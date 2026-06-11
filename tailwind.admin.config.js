module.exports = {
  darkMode: 'class',
  content: [
    './app/Views/admin/**/*.php',
    './app/Views/admin/*.php',
    './app/Views/admin/layouts/*.php',
    './app/Views/admin/components/*.php',
    './app/Views/admin/auth/*.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Outfit', 'Inter', 'system-ui', 'sans-serif'],
        mono: ['IBM Plex Mono', 'ui-monospace', 'Menlo', 'monospace'],
      },
      colors: {
        brand: {
          50: '#ecf3ff',
          100: '#dde9ff',
          500: '#465fff',
          600: '#3641f5',
          700: '#2a31d8',
        },
      },
      boxShadow: {
        tailadmin: '0 1px 3px rgba(16, 24, 40, 0.08), 0 1px 2px rgba(16, 24, 40, 0.04)',
      },
    },
  },
  plugins: [],
};
