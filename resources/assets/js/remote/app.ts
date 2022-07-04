import { createApp } from 'vue'
import { clickaway } from '@/directives'
import App from './App.vue'

createApp(App)
  .directive('koel-clickaway', clickaway)
  .mount('#app')
