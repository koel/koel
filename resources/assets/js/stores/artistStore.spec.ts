import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import factory from '@/__tests__/factory'
import { http } from '@/services/http'
import { playableStore } from '@/stores/playableStore'
import { artistStore } from '@/stores/artistStore'

describe('artistStore', () => {
  const h = createHarness({
    beforeEach: () => {
      artistStore.vault.clear()
      artistStore.state.artists = []
    },
  })

  it('gets an artist by ID', () => {
    const artist = h.factory('artist')
    artistStore.vault.set(artist.id, artist)
    expect(artistStore.byId(artist.id)).toEqual(artist)
  })

  it('removes artists by IDs', () => {
    const artists = h.factory('artist', 3)
    artists.forEach(artist => artistStore.vault.set(artist.id, artist))
    artistStore.state.artists = artists

    artistStore.removeByIds([artists[0].id, artists[1].id])

    expect(artistStore.vault.size).toBe(1)
    expect(artistStore.vault.has(artists[0].id)).toBe(false)
    expect(artistStore.vault.has(artists[1].id)).toBe(false)
    expect(artistStore.state.artists).toEqual([artists[2]])
  })

  it('identifies an unknown artist', () => {
    const artist = factory.states('unknown')('artist')

    expect(artistStore.isUnknown(artist)).toBe(true)
    expect(artistStore.isUnknown(artist.name)).toBe(true)
    expect(artistStore.isUnknown(h.factory('artist'))).toBe(false)
  })

  it('identifies the various artist', () => {
    const artist = factory.states('various')('artist')

    expect(artistStore.isVarious(artist)).toBe(true)
    expect(artistStore.isVarious(artist.name)).toBe(true)
    expect(artistStore.isVarious(h.factory('artist'))).toBe(false)
  })

  it('identifies a standard artist', () => {
    expect(artistStore.isStandard(factory.states('unknown')('artist'))).toBe(false)
    expect(artistStore.isStandard(factory.states('various')('artist'))).toBe(false)
    expect(artistStore.isStandard(h.factory('artist'))).toBe(true)
  })

  it('syncs artists with the vault', () => {
    const artist = h.factory('artist', { name: 'Led Zeppelin' })

    artistStore.syncWithVault(artist)
    expect(artistStore.vault.get(artist.id)).toEqual(artist)

    artist.name = 'Pink Floyd'
    artistStore.syncWithVault(artist)

    expect(artistStore.vault.size).toBe(1)
    expect(artistStore.vault.get(artist.id)?.name).toBe('Pink Floyd')
  })

  it('resolves an artist', async () => {
    const artist = h.factory('artist')
    const getMock = h.mock(http, 'get').mockResolvedValueOnce(artist)

    expect(await artistStore.resolve(artist.id)).toEqual(artist)
    expect(getMock).toHaveBeenCalledWith(`artists/${artist.id}`)

    // next call shouldn't make another request
    expect(await artistStore.resolve(artist.id)).toEqual(artist)
    expect(getMock).toHaveBeenCalledOnce()
  })

  it('paginates', async () => {
    const artists = h.factory('artist', 3)

    h.mock(http, 'get').mockResolvedValueOnce({
      data: artists,
      links: {
        next: '/artists?page=2',
      },
      meta: {
        current_page: 1,
      },
    })

    expect(await artistStore.paginate({
      favorites_only: false,
      page: 1,
      sort: 'name',
      order: 'asc',
    })).toEqual(2)

    expect(artistStore.state.artists).toEqual(artists)
    expect(artistStore.vault.size).toBe(3)
  })

  it('toggles favorite', async () => {
    const artist = h.factory('artist', { favorite: false })
    artistStore.syncWithVault(artist)

    const postMock = h.mock(http, 'post').mockResolvedValueOnce(h.factory('favorite', {
      favoriteable_type: 'artist',
      favoriteable_id: artist.id,
    }))

    await artistStore.toggleFavorite(artist)

    expect(postMock).toHaveBeenCalledWith('favorites/toggle', { type: 'artist', id: artist.id })
    expect(artist.favorite).toBe(true)

    postMock.mockResolvedValue(null)
    await artistStore.toggleFavorite(artist)

    expect(postMock).toHaveBeenNthCalledWith(2, 'favorites/toggle', { type: 'artist', id: artist.id })
    expect(artist.favorite).toBe(false)
  })

  it('updates artist', async () => {
    const artist = h.factory('artist', { name: 'Led Zeppelin' })
    artistStore.syncWithVault(artist)

    const updatedArtist = {
      ...artist,
      name: 'Pink Floyd',
      image: 'foo',
    }

    const updateData = {
      name: 'Pink Floyd',
      image: 'foo',
    }

    const putMock = h.mock(http, 'put').mockResolvedValue(updatedArtist)
    const syncPropsMock = h.mock(playableStore, 'syncArtistProperties')

    await artistStore.update(artist, updateData)

    expect(putMock).toHaveBeenCalledWith(`artists/${artist.id}`, updateData)
    expect(syncPropsMock).toHaveBeenCalledWith(updatedArtist)
  })

  it('removes image', async () => {
    const artist = h.factory('artist')
    artistStore.syncWithVault(artist)
    const deleteMock = h.mock(http, 'delete').mockResolvedValue(null)

    await artistStore.removeImage(artist)

    expect(deleteMock).toHaveBeenCalledWith(`artists/${artist.id}/image`)
    expect(artistStore.byId(artist.id)?.image).toBe('')
  })
})
