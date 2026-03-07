import { defineConfig } from 'vite'
import path from 'path'

/**
 * Separate Vite config for building the service worker.
 * The SW must be a single self-contained file output to public/sw.js.
 */
export default defineConfig({
  publicDir: false,
  build: {
    lib: {
      entry: path.resolve(__dirname, 'resources/assets/js/service-worker.ts'),
      formats: ['es'],
      fileName: () => 'sw.js',
    },
    outDir: 'public',
    emptyOutDir: false,
    sourcemap: false,
    minify: false,
  },
})
