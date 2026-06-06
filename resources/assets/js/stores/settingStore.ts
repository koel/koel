import { reactive } from 'vue'
import { merge } from 'lodash'
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
}
// NEXTCLOUD INTEGRATION STATE - SWE PROJECT 2026
// Frontend'de Nextcloud'un senkronizasyon durumunu takip eden veri deposu
export const nextcloudStore = {
  state: {
    isSyncing: false,
    lastSyncDate: null as string | null,
    connectionStatus: 'disconnected'
  },
  
  setSyncStatus(status: boolean) {
    this.state.isSyncing = status;
  },
  
  updateLastSync(date: string) {
    this.state.lastSyncDate = date;
  }
};