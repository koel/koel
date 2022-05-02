/// <reference types="vitest" />
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/assets/js'),
      '#': path.resolve(__dirname, './resources/assets/sass')
    }
  },
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `
          @import "#/partials/_vars.scss";
          @import "#/partials/_mixins.scss";
          `
      }
    }
  },
  define: {
    KOEL_ENV: '""'
  },
  test: {
    environment: 'happy-dom'
  },
})
