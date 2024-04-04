import { createApp } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { clickaway } from '@/directives'
import '@/../css/remote.pcss'
import App from './App.vue'

createApp(App)
  .component('Icon', FontAwesomeIcon)
  .directive('koel-clickaway', clickaway)
  .mount('#app')
