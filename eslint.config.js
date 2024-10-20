import config from '@antfu/eslint-config'

export default config(
  {
    formatters: true,
  },
  {
    files: ['resources/**/*.{js,ts,css,pcss,vue}'],
    rules: {
      'antfu/top-level-function': 'off',
      'curly': ['error', 'all'],
      'no-case-declarations': 'off',
      'no-multi-str': 'off',
      'no-new': 'off',
      'perfectionist/sort-imports': 'off',
      'style/arrow-parens': ['error', 'as-needed'],
      'style/brace-style': ['error', '1tbs'],
      'style/new-parens': 'off',
      'node/prefer-global/process': ['error', 'always'],
      'style/space-before-function-paren': ['error', 'always'],
      'ts/ban-ts-comment': 'off',
      'vue/block-order': ['error', { 'order': ['template', 'script', 'style'] }],
      'vue/no-lone-template': 'off',
      'vue/singleline-html-element-content-newline': 'off',
    },
  },
).prepend({
  ignores: [
    'resources/assets/tsconfig.json',
    'resources/assets/css/vendor/**',
    'resources/assets/js/visualizers/**',
  ]
})
