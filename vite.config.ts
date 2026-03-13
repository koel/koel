/// <reference types="vite-plus/test" />
import { defineConfig } from 'vite-plus'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { resolve } from 'path'
import { visualizer } from 'rollup-plugin-visualizer'

const __dirname = import.meta.dirname

export default defineConfig({
  staged: {
    '**/*.php': ['composer cs'],
    'resources/assets/**/*.{js,ts,css,pcss,vue}': ['vp check --fix'],
    'cypress/**/*.ts': ['vp check --fix'],
  },
  lint: {
    plugins: ['typescript', 'vue', 'import'],
    categories: {
      correctness: 'error',
      suspicious: 'warn',
      pedantic: 'off',
      nursery: 'off',
      style: 'off',
      perf: 'warn',
      restriction: 'off',
    },
    rules: {
      'no-case-declarations': 'off',
      'no-new': 'off',
      'no-shadow': 'off',
      'no-unused-expressions': 'off',
      'no-unassigned-import': 'off',
      'no-await-in-loop': 'off',
      'typescript/ban-ts-comment': 'off',
      'unbound-method': 'warn',
      'no-floating-promises': 'warn',
      'restrict-template-expressions': 'warn',
      'no-redundant-type-constituents': 'warn',
      'no-duplicate-type-constituents': 'warn',
      'no-base-to-string': 'warn',
      'await-thenable': 'warn',
    },
    ignorePatterns: [
      'resources/assets/tsconfig.json',
      'resources/assets/css/vendor/**',
      'resources/assets/js/visualizers/**',
    ],
    options: {
      typeAware: true,
      // TODO: enable typeCheck once tsgolint supports tsconfig paths resolution
      // typeCheck: true,
    },
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
