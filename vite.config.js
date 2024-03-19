/// <reference types="vitest" />
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import path from 'path'

export default defineConfig({
  plugins: [
    vue(),
    laravel.default({
      input: [
        'resources/assets/js/app.ts',
        'resources/assets/js/remote/app.ts'
      ],
      refresh: true
    }),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/assets/js'),
      '#': path.resolve(__dirname, './resources/assets/sass'),
      '@modules': path.resolve(__dirname, './node_modules')
    }
  },
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: '@import "#/partials/_mixins.scss";'
      }
    }
  },
  test: {
    environment: 'jsdom',
    setupFiles: path.resolve(__dirname, './resources/assets/js/__tests__/setup.ts')
  }
})
