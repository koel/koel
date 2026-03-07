/// <reference types="vitest" />
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import path from 'path'
import { visualizer } from 'rollup-plugin-visualizer'

export default defineConfig({
  plugins: [
    vue(),
    laravel({
      input: [
        'resources/assets/js/app.ts',
        'resources/assets/js/remote/app.ts'
      ],
      refresh: true
    }),
    visualizer({
      filename: 'stats.html'
    })
  ],
  build: {
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (!id.includes('node_modules')) return

          if (id.includes('three')) return 'three'
          if (id.includes('pusher-js')) return 'pusher'
          if (id.includes('plyr')) return 'plyr'
          if (id.includes('@floating-ui')) return 'floating-ui'
          if (id.includes('vue') || id.includes('@vue')) return 'vue'
          if (id.includes('lodash')) return 'lodash'
          if (id.includes('axios')) return 'axios'
        },
      },
    },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/assets/js'),
      '@modules': path.resolve(__dirname, './node_modules')
    }
  },
  test: {
    environment: 'jsdom',
    setupFiles: path.resolve(__dirname, './resources/assets/js/__tests__/setup.ts')
  }
})
