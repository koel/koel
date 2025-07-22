import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { http } from '@/services/http'
import { podcastStore } from '@/stores/podcastStore'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      podcastStore.state.podcasts = []
    })
  }

  protected test () {
    it('gets a podcast by id', () => {
      const podcast = factory('podcast')
      podcastStore.state.podcasts.push(podcast)

      const foundPodcast = podcastStore.byId(podcast.id)
      expect(foundPodcast).toEqual(podcast)
    })

    it('resolves to a local podcast if available', async () => {
      const podcast = factory('podcast')
      podcastStore.state.podcasts.push(podcast)

      const resolvedPodcast = await podcastStore.resolve(podcast.id)
      expect(resolvedPodcast).toEqual(podcast)
    })

    it('fetches a podcast by id if not found locally', async () => {
      const podcast = factory('podcast')
      const getMock = this.mock(podcastStore, 'fetchOne').mockResolvedValue(podcast)

      const resolvedPodcast = await podcastStore.resolve(podcast.id)
      expect(getMock).toHaveBeenCalledWith(podcast.id)
      expect(resolvedPodcast).toEqual(podcast)
    })

    it('stores a new podcast', async () => {
      const podcast = factory('podcast')
      const postMock = this.mock(http, 'post').mockResolvedValue(podcast)

      const storedPodcast = await podcastStore.store('https://example.com/podcast')
      expect(postMock).toHaveBeenCalledWith('podcasts', { url: 'https://example.com/podcast' })
      expect(storedPodcast).toEqual(podcast)
      expect(podcastStore.state.podcasts).toContain(podcast)
    })

    it('fetches all podcasts', async () => {
      const podcasts = factory('podcast', 3)
      const getMock = this.mock(http, 'get').mockResolvedValue(podcasts)

      await podcastStore.fetchAll()

      expect(getMock).toHaveBeenCalledWith('podcasts?favorites_only=false')
      expect(podcastStore.state.podcasts).toEqual(podcasts)
    })

    it('fetches favorite podcasts only', async () => {
      const podcasts = factory('podcast', 3)
      const getMock = this.mock(http, 'get').mockResolvedValue(podcasts)

      await podcastStore.fetchAll(true)

      expect(getMock).toHaveBeenCalledWith('podcasts?favorites_only=true')
      expect(podcastStore.state.podcasts).toEqual(podcasts)
    })

    it('fetches a single podcast', async () => {
      const podcast = factory('podcast')
      const getMock = this.mock(http, 'get').mockResolvedValue(podcast)

      const fetchedPodcast = await podcastStore.fetchOne(podcast.id)
      expect(getMock).toHaveBeenCalledWith(`podcasts/${podcast.id}`)
      expect(fetchedPodcast).toEqual(podcast)
      expect(podcastStore.byId(podcast.id)).toEqual(podcast)
    })

    it('unsubscribes from a podcast', async () => {
      const podcast = factory('podcast')
      podcastStore.state.podcasts.push(podcast)
      const deleteMock = this.mock(http, 'delete').mockResolvedValue({})

      await podcastStore.unsubscribe(podcast)

      expect(deleteMock).toHaveBeenCalledWith(`podcasts/${podcast.id}/subscriptions`)
      expect(podcastStore.state.podcasts).not.toContain(podcast)
    })

    it('resets the podcast store', () => {
      podcastStore.state.podcasts.push(...factory('podcast', 3))

      podcastStore.reset()

      expect(podcastStore.state.podcasts).toEqual([])
    })

    it('toggles a podcast favorite status', async () => {
      const podcast = factory('podcast', { favorite: false })
      podcastStore.state.podcasts.push(podcast)

      const postMock = this.mock(http, 'post').mockResolvedValueOnce(factory('favorite', {
        favoriteable_type: 'podcast',
        favoriteable_id: podcast.id,
      }))

      await podcastStore.toggleFavorite(podcast)

      expect(postMock).toHaveBeenCalledWith('favorites/toggle', { type: 'podcast', id: podcast.id })
      expect(podcast.favorite).toBe(true)

      postMock.mockResolvedValue(null)
      await podcastStore.toggleFavorite(podcast)

      expect(postMock).toHaveBeenNthCalledWith(2, 'favorites/toggle', { type: 'podcast', id: podcast.id })
      expect(podcast.favorite).toBe(false)
    })
  }
}
