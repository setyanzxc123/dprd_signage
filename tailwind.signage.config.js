module.exports = {
  darkMode: ['class', '[data-signage-theme="dark"]'],
  content: [
    './app/Views/signage/**/*.php',
    './app/Views/signage/*.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'Segoe UI', 'system-ui', 'sans-serif'],
        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
      },
      colors: {
        signage: {
          blue: '#2563eb',
          cyan: '#60a5fa',
          navy: '#0a1628',
        },
      },
    },
  },
  plugins: [],
};
