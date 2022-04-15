import { http } from '@/services'
import { alerts } from '@/utils'
import stub from '@/stubs/settings'

export const settingStore = {
  stub,

  state: {
    settings: {}
  },

  init (settings: object) {
    this.state.settings = settings
  },

  get all () {
    return this.state.settings
  },

  async update (): Promise<void> {
    await http.post('settings', this.all)
    alerts.success('Settings saved.')
  }
} as { [key: string]: any }
