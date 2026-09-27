import { reactive } from 'vue'
import { merge } from 'lodash-es'
import { http } from '@/services/http'

export const settingStore = {
  state: reactive<Settings>({
    media_path: '',
  }),

  init(settings: Settings) {
    merge(this.state, settings)
  },

  async updateMediaPath(path: string) {
    await http.put('settings/media-path', {
      path,
    })

    this.state.media_path = path
  },

  async updateBranding(data: Partial<Branding>) {
    await http.put('settings/branding', data)
  },

  async updateAi(data: { enabled: boolean; provider: AiProvider; api_key?: string }) {
    this.state.ai = await http.put<AiSettings>('settings/ai', data)
  },
}
