import { reactive } from 'vue'
import { merge } from 'lodash'
import { http } from '@/services/http'

export const settingStore = {
  state: reactive<Settings>({
    media_path: '',
  }),

  init (settings: Settings) {
    merge(this.state, settings)
  },

  async update (settings: Settings) {
    await http.put('settings', settings)
    merge(this.state, settings)
  },
}
