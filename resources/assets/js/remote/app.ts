import './staticLoader'
import { createApp } from 'vue'
import App from './App.vue'
import { clickaway } from '@/directives'

createApp(App)
  .directive('koel-clickaway', clickaway)
  .mount('#app')
