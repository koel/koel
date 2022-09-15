import { reactive } from 'vue'
import { http } from '@/services'
import { merge } from 'lodash'

export const settingStore = {
  state: reactive<Settings>({
    media_path: ''
  }),

  init (settings: Settings) {
    merge(this.state, settings)
  },

  async update (settings: Settings) {
    await http.put('settings', settings)
    merge(this.state, settings)
  }
}
