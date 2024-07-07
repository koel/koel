import { reactive } from 'vue'
import { http } from '@/services'

export const podcastStore = {
  state: reactive({
    podcasts: [] as Podcast[]
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

  async fetchAll () {
    this.state.podcasts = await http.get<Podcast[]>('podcasts')
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
}
