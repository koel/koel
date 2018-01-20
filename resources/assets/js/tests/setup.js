// setup JSDOM
require('jsdom-global')()

// make sure polyfill is loaded before generators
require('babel-polyfill')

require('chai').should()

require('vue-test-helpers')()

// make common utils available globally as well
global.Vue = require('vue')
global.expect = require('expect')
global.sinon = require('sinon')
global._ = require('lodash')
window.__UNIT_TESTING__ = true
