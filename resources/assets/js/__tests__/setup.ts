import '@babel/polyfill'
import Vue from 'vue'
import lodash from 'lodash'
import setupVueTestHelper from 'vue-test-helpers'
import { noop } from './__helpers__'
import { focus, clickaway, droppable } from '@/directives'

// make common utils available globally as well
global.Vue = Vue
global._ = lodash
window.__UNIT_TESTING__ = true

global.noop = noop

// stub this to make Virtual Scroller silent
global.IntersectionObserver = class IntersectionObserver {
  observe () {
    return null
  }

  unobserve () {
    return null
  }

  disconnect () {
    return null
  }
}

global.Vue.directive('koel-focus', focus)
global.Vue.directive('koel-clickaway', clickaway)
global.Vue.directive('koel-droppable', droppable)

setupVueTestHelper({ registerGlobals: false })

/* eslint @typescript-eslint/no-unused-vars: 0 */
// execCommand isn't supported by jsDom (yet?)
document.execCommand = (command: string): boolean => {
  return false
}
