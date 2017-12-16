// setup JSDOM
require('jsdom-global')()

// make sure polyfill is loaded before generators
require('babel-polyfill')

require('chai').should()

// make common utils available globally as well
global.Vue = require('vue')
global.faker = require('faker')
global.expect = require('expect')
global.sinon = require('sinon')
global._ = require('lodash')
const testUtils = require('vue-test-utils')
global.shallow = testUtils.shallow
global.mount = testUtils.mount
window.__UNIT_TESTING__ = true
