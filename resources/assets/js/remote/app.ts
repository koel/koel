import { createApp } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import '@/../css/remote.pcss'
import App from './App.vue'

createApp(App)
  .component('Icon', FontAwesomeIcon)
  .mount('#app')
