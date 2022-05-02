module.exports = {
  moduleFileExtensions: [
    'ts',
    'js',
    'vue'
  ],
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>\/resources\/assets\/js\/$1'
  },
  transform: {
    '\\.[tj]s$': 'babel-jest',
    '\\.vue$': '@vue/vue3-jest',
    '\\.(svg|gif|jpg|png)$': '<rootDir>/resources/assets/js/__tests__/__transformers__/image.js'
  },
  testMatch: ['**/*.spec.ts'],
  globals: {
    KOEL_ENV: '',
    NODE_ENV: 'test'
  },
  // setupFilesAfterEnv: ['<rootDir>/js/__tests__/setup.ts'],
  verbose: true,
  // collectCoverage: true,
  // coverageReporters: ['lcov', 'json', 'html'],
  // coverageDirectory: '<rootDir>/js/__tests__/__coverage__',
  // coveragePathIgnorePatterns: ['/node_modules/', '/__tests__/', '/stubs/', '/libs/']
}
