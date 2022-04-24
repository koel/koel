import './staticLoader'
import { httpService } from '@/services'
import App from './app.vue'
import { createApp } from 'vue'

httpService.init()
createApp(App).mount('#app')
