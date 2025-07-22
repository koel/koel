import type { Reactive } from 'vue'
import { reactive } from 'vue'
import { http } from '@/services/http'

export const podcastStore = {
  state: reactive({
    podcasts: [] as Podcast[],
  }),

  byId (id: Podcast['id']) {
    return this.state.podcasts.find(podcast => podcast.id === id)
  },

  async resolve (id: Podcast['id']) {
    return this.byId(id) ?? await this.fetchOne(id)
  },

  async store (url: string) {
    const podcast = await http.post<Podcast>('podcasts', { url })
    this.state.podcasts.push(podcast)

    return podcast
  },

  async fetchAll (favoritesOnly = false) {
    this.state.podcasts = await http.get<Podcast[]>(`podcasts?favorites_only=${favoritesOnly}`)
    return this.state.podcasts
  },

  async fetchOne (id: Podcast['id']) {
    this.state.podcasts.push(await http.get<Podcast>(`podcasts/${id}`))
    return this.byId(id)!
  },

  async unsubscribe (podcast: Podcast) {
    await http.delete(`podcasts/${podcast.id}/subscriptions`)
    this.state.podcasts = this.state.podcasts.filter(p => p.id !== podcast.id)
  },

  reset () {
    this.state.podcasts = []
  },

  async toggleFavorite (podcast: Reactive<Podcast>) {
    // Don't wait for the HTTP response to update the status, just toggle right away.
    // We'll update the liked status again after the HTTP request.
    podcast.favorite = !podcast.favorite

    const favorite = await http.post<Favorite | null>(`favorites/toggle`, {
      type: 'podcast',
      id: podcast.id,
    })

    podcast.favorite = Boolean(favorite)
  },
}
