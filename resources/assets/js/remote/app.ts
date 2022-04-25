import './staticLoader'
import { createApp } from 'vue'
import { httpService } from '@/services'
import App from './App.vue'
import { clickaway } from '@/directives'

httpService.init()

const app = createApp(App)
app.directive('koel-clickaway', clickaway)

app.mount('#app')
