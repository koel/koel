/**
 * By default jest doesn't transform files in node_modules.
 * List names of the libraries we want to whitelist here, e.g., those export ES6 modules.
 */
const forceTransformModules = [
  '@phanan/vuebus'
]

module.exports = {
  moduleFileExtensions: [
    'ts',
    'js',
    'vue'
  ],
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/js/$1'
  },
  transform: {
    '^.+\\.[tj]s$': 'ts-jest',
    '.*\\.(vue)$': 'vue-jest',
    '^.+\\.(svg|gif|jpg|png)$': '<rootDir>/js/__tests__/__transformers__/image.js'
  },
  snapshotSerializers: [
    '<rootDir>/node_modules/jest-serializer-vue'
  ],
  testMatch: ['**/__tests__/**/*.spec.ts'],
  transformIgnorePatterns: [
    `node_modules/(?!(${forceTransformModules.join('|')})/)`
  ],
  globals: {
    KOEL_ENV: ''
  },
  setupFilesAfterEnv: ['<rootDir>/js/__tests__/setup.ts'],
  verbose: true,
  collectCoverage: true,
  coverageReporters: ['lcov', 'json', 'html'],
  coverageDirectory: '<rootDir>/js/__tests__/__coverage__',
  coveragePathIgnorePatterns: ['/node_modules/', '/__tests__/', '/stubs/', '/libs/']
}
