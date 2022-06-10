import { reactive } from 'vue'
import { httpService } from '@/services'

export const settingStore = {
  state: reactive<Settings>({
    media_path: ''
  }),

  init (settings: Settings) {
    Object.assign(this.state, settings)
  },

  async update (settings: Settings) {
    await httpService.put('settings', settings)
    Object.assign(this.state, settings)
  }
}
