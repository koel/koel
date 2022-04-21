import { reactive } from 'vue'
import { http } from '@/services'

export const settingStore = {
  state: reactive<Settings>({
    media_path: ''
  }),

  init (settings: Settings) {
    Object.assign(this.state, settings)
  },

  get all () {
    return this.state
  },

  async update (settings: Settings) {
    await http.put('settings', settings)
    Object.assign(this.state, settings)
  }
}
