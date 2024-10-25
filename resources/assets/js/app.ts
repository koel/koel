import { FontAwesomeIcon, FontAwesomeLayers } from '@fortawesome/vue-fontawesome'
import { createApp } from 'vue'
import { focus } from '@/directives/focus'
import { tooltip } from '@/directives/tooltip'
import { hideBrokenIcon } from '@/directives/hideBrokenIcon'
import { overflowFade } from '@/directives/overflowFade'
import { newTab } from '@/directives/newTab'
import { RouterKey } from '@/symbols'
import Router from '@/router'
import '@/../css/app.pcss'
import App from './App.vue'

createApp(App)
  .provide(RouterKey, new Router())
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
