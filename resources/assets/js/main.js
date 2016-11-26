import Vue from 'vue'

import { event } from './utils'
import { http } from './services'
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
