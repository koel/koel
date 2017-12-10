// setup JSDOM
require('jsdom-global')()

// make common utils to be available globally
global.expect = require('expect')
global.sinon = require('sinon')
const testUtils = require('vue-test-utils')
global.shallow = testUtils.shallow
global.mount = testUtils.mount
