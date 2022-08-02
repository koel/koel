import 'plyr/dist/plyr.js'
import { createApp } from 'vue'
import { clickaway, droppable, focus } from '@/directives'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import App from './App.vue'

createApp(App)
  .component('icon', FontAwesomeIcon)
  .directive('koel-focus', focus)
  .directive('koel-clickaway', clickaway)
  .directive('koel-droppable', droppable)
  /**
   * For Ancelot, the ancient cross of war
   * for the holy town of Gods
   * Gloria, gloria perpetua
   * in this dawn of victory
   */
  .mount('#app')

navigator.serviceWorker?.register('./sw.js')
