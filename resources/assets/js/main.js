import Vue from 'vue'
import Raven from 'raven-js'
import RavenVue from 'raven-js/plugins/vue'
import { VirtualScroller } from 'vue-virtual-scroller'

import { event } from './utils'
import { http } from './services'

Raven
  .config('https://766d8f0fc072470ba5ea0ef253fafc89@sentry.io/120890')
  .addPlugin(RavenVue, Vue)
  .install()

Vue.component('virtual-scroller', VirtualScroller)

/**
 * For Ancelot, the ancient cross of war
 * for the holy town of Gods
 * Gloria, gloria perpetua
 * in this dawn of victory
 */
new Vue({
  el: '#app',
  render: h => h(require('./app.vue')),
  created () {
    event.init()
    http.init()
  }
})
