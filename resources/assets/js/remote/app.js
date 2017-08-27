import './static-loader'
import Vue from 'vue'
import { http } from '../services'

new Vue({
  el: '#app',
  render: h => h(require('./app.vue')),
  created () {
    http.init()
  }
})
