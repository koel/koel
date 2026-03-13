/// <reference types="vite-plus/test" />
import { defineConfig } from 'vite-plus'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { resolve } from 'path'
import { visualizer } from 'rollup-plugin-visualizer'

const __dirname = import.meta.dirname

export default defineConfig({
  staged: {
    '**/*.php': [
      'composer cs'
    ],
    'resources/assets/**/*.{js,ts,css,pcss,vue}': [
      'vp fmt --write',
      'vp lint --fix'
    ],
    'resources/assets/**/*.{ts,vue}': "bash -c 'vue-tsc --noEmit -p resources/assets/tsconfig.typecheck.json'",
    'cypress/**/*.ts': [
      'vp fmt --write',
      'vp lint --fix'
    ]
  },
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
    cssMinify: 'esbuild'
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, './resources/assets/js'),
      '@modules': resolve(__dirname, './node_modules'),
      'lodash': 'lodash-es'
    }
  },
  test: {
    environment: 'jsdom',
    setupFiles: resolve(__dirname, './resources/assets/js/__tests__/setup.ts')
  }
})
