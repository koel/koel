/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/assets/js/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        'k-fg': 'var(--color-fg)',
        'k-fg-3': 'color-mix(in srgb, var(--color-fg), transparent 97%)',
        'k-fg-5': 'color-mix(in srgb, var(--color-fg), transparent 95%)',
        'k-fg-10': 'color-mix(in srgb, var(--color-fg), transparent 90%)',
        'k-fg-20': 'color-mix(in srgb, var(--color-fg), transparent 80%)',
        'k-fg-30': 'color-mix(in srgb, var(--color-fg), transparent 70%)',
        'k-fg-40': 'color-mix(in srgb, var(--color-fg), transparent 60%)',
        'k-fg-50': 'color-mix(in srgb, var(--color-fg), transparent 50%)',
        'k-fg-60': 'color-mix(in srgb, var(--color-fg), transparent 40%)',
        'k-fg-70': 'color-mix(in srgb, var(--color-fg), transparent 30%)', // main text color
        'k-fg-80': 'color-mix(in srgb, var(--color-fg), transparent 20%)',
        'k-fg-90': 'color-mix(in srgb, var(--color-fg), transparent 10%)',
        'k-fg-95': 'color-mix(in srgb, var(--color-fg), transparent 5%)',
        'k-fg-input': 'var(--color-fg)',
        'k-bg': 'var(--color-bg)',
        'k-bg-10': 'color-mix(in srgb, var(--color-bg), transparent 90%)',
        'k-bg-20': 'color-mix(in srgb, var(--color-bg), transparent 80%)',
        'k-bg-30': 'color-mix(in srgb, var(--color-bg), transparent 70%)',
        'k-bg-40': 'color-mix(in srgb, var(--color-bg), transparent 60%)',
        'k-bg-50': 'color-mix(in srgb, var(--color-bg), transparent 50%)',
        'k-bg-60': 'color-mix(in srgb, var(--color-bg), transparent 40%)',
        'k-bg-70': 'color-mix(in srgb, var(--color-bg), transparent 30%)',
        'k-bg-80': 'color-mix(in srgb, var(--color-bg), transparent 20%)',
        'k-bg-90': 'color-mix(in srgb, var(--color-bg), transparent 10%)',
        'k-bg-95': 'color-mix(in srgb, var(--color-bg), transparent 5%)',
        'k-bg-context-menu': 'var(--color-bg)',
        'k-bg-input': 'color-mix(in srgb, var(--color-fg), transparent 95%)',
        'k-highlight': 'var(--color-highlight)',
        'k-highlight-fg': 'var(--color-highlight-fg)',
        'k-success': 'var(--color-success)',
        'k-danger': 'var(--color-danger)',
        'k-primary': 'var(--color-primary)',
        'k-love': 'var(--color-love)',
      },
      spacing: {
        'k-header-height': 'var(--header-height)',
        'k-footer-height': 'var(--footer-height)',
        'k-sidebar-width': 'var(--sidebar-width)',
        'k-side-sheet-width': 'var(--side-sheet-width)',
      },
      screens: {
        'no-hover': { 'raw': '(hover: none)' },
      }
    },
    variants: {
      extend: {
        gradientColorStops: ['before', 'after'], // enable from-/to-/via- for pseudo-elements
      },
    },
  },
  plugins: [],
}

