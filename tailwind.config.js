/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/assets/js/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        'k-text-primary': 'var(--color-text-primary)',
        'k-text-secondary': 'var(--color-text-secondary)',
        'k-text-input': 'var(--color-text-input)',
        'k-bg-primary': 'var(--color-bg-primary)',
        'k-bg-secondary': 'var(--color-bg-secondary)',
        'k-bg-context-menu': 'var(--color-bg-context-menu)',
        'k-bg-input': 'var(--color-bg-input)',
        'k-border': 'var(--color-border)',
        'k-highlight': 'var(--color-highlight)',
        'k-accent': 'var(--color-accent)',
        'k-success': 'var(--color-success)',
        'k-danger': 'var(--color-danger)',
        'k-primary': 'var(--color-primary)',
        'k-love': 'var(--color-love)',
      },
      spacing: {
        'k-header-height': 'var(--header-height)',
        'k-footer-height': 'var(--footer-height)',
        'k-sidebar-width': 'var(--sidebar-width)',
        'k-extra-drawer-width': 'var(--extra-drawer-width)',
      },
      screens: {
        'no-hover': {'raw': '(hover: none)'},
      }
    },
  },
  plugins: [],
}

