import { reactive } from 'vue'
import { http } from '@/services'
import { alerts } from '@/utils'
import stub from '@/stubs/settings'

export const settingStore = {
  stub,

  state: reactive<Settings>({
    media_path: ''
  }),

  init (settings: Settings) {
    Object.assign(this.state, settings)
  },

  get all () {
    return this.state
  },

  async update (): Promise<void> {
    await http.post('settings', this.all)
    alerts.success('Settings saved.')
  }
}
