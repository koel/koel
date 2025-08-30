import type { Reactive } from 'vue'
import { reactive } from 'vue'
import { http } from '@/services/http'
import { authService } from '@/services/authService'
import { merge } from 'lodash'
import { arrayify } from '@/utils/helpers'
import { commonStore } from '@/stores/commonStore'

export type RadioStationData = Pick<RadioStation, 'name' | 'url' | 'logo' | 'description' | 'is_public'>

export const radioStationStore = {
  // Unlike songs, we don't expect a lot of radio stations per user.
  // Keep it simple by using state.stations only (without the vault/local cache algorithm).
  state: reactive({
    stations: [] as RadioStation[],
  }),

  byId (id: RadioStation['id']) {
    return this.state.stations.find(station => station.id === id)
  },

  sync (stations: MaybeArray<RadioStation>) {
    return arrayify(stations).map(station => {
      let local = this.byId(station.id)

      if (local) {
        merge(local, station)
      } else {
        local = reactive(station)
        local.playback_state = 'Stopped'
        this.state.stations.push(local)
      }

      return local
    })
  },

  getSourceUrl: (station: RadioStation) => {
    return `${commonStore.state.cdn_url}radio/stream/${station.id}?t=${authService.getAudioToken()}`
  },

  // Unlike playable/playable, we don't support queueing radio stations and thus don't need a dedicated queue for them.
  // Rather, we keep track of the currently playing station inside the radio station store itself.
  get current () {
    return this.state.stations.find(station => station.playback_state !== 'Stopped') || null
  },

  async store (data: Reactive<RadioStationData>) {
    return this.sync(await http.post<RadioStation>('radio/stations', data))[0]
  },

  async fetchAll (favoritesOnly = false) {
    return this.sync(await http.get<RadioStation[]>(`radio/stations?favorites_only=${favoritesOnly}`))
  },

  async update (station: Reactive<RadioStation>, data: RadioStationData) {
    return this.sync(await http.put<RadioStation>(`radio/stations/${station.id}`, data))[0]
  },

  async delete (station: Reactive<RadioStation>) {
    await http.delete(`radio/stations/${station.id}`)
    this.state.stations = this.state.stations.filter(({ id }) => id !== station.id)
  },

  toggleFavorite: async (station: Reactive<RadioStation>) => {
    station.favorite = !station.favorite

    const favorite = await http.post<Favorite | null>(`favorites/toggle`, {
      type: 'radio-station',
      id: station.id,
    })

    station.favorite = Boolean(favorite)
  },

  async removeLogo (station: Reactive<RadioStation>) {
    await http.delete(`radio/stations/${station.id}/logo`)
    this.byId(station.id)!.logo = null
  },
}
