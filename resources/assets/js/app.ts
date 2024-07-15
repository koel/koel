import { createApp } from 'vue'
import { focus, hideBrokenIcon, newTab, overflowFade, tooltip } from '@/directives'
import { FontAwesomeIcon, FontAwesomeLayers } from '@fortawesome/vue-fontawesome'
import { RouterKey } from '@/symbols'
import { routes } from '@/config'
import Router from '@/router'
import '@/../css/app.pcss'
import App from './App.vue'

createApp(App)
  .provide(RouterKey, new Router(routes))
  .component('Icon', FontAwesomeIcon)
  .component('IconLayers', FontAwesomeLayers)
  .directive('koel-focus', focus)
  .directive('koel-tooltip', tooltip)
  .directive('koel-hide-broken-icon', hideBrokenIcon)
  .directive('koel-overflow-fade', overflowFade)
  .directive('koel-new-tab', newTab)
  /**
   * For Ancelot, the ancient cross of war
   * for the holy town of Gods
   * Gloria, gloria perpetua
   * in this dawn of victory
   */
  .mount('#app')

navigator.serviceWorker?.register('./sw.js')
