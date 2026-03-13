import { defineConfig } from 'vite-plus'
import { resolve } from 'path'

const __dirname = import.meta.dirname

/**
 * Separate Vite config for building the service worker.
 * The SW must be a single self-contained file output to public/sw.js.
 */
export default defineConfig({
  publicDir: false,
  build: {
    lib: {
      entry: resolve(__dirname, 'resources/assets/js/service-worker.ts'),
      formats: ['es'],
      fileName: () => 'sw.js',
    },
    outDir: 'public',
    emptyOutDir: false,
    sourcemap: false,
    minify: false,
  },
})
