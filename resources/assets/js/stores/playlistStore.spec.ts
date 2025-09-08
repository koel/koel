import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import factory from '@/__tests__/factory'
import { cache } from '@/services/cache'
import { http } from '@/services/http'
import { playlistStore } from '@/stores/playlistStore'

const ruleGroups: SmartPlaylistRuleGroup[] = [
  {
    id: 'c328a77e-3edf-46ed-8c8b-398ec443e6ad',
    rules: [
      {
        id: '72f58da9-7350-488a-a1ee-c7c0edbfcc99',
        model: {
          name: 'artist.name',
          type: 'text',
          label: 'Artist',
        },
        operator: 'is',
        value: ['Elvis Presley'],
      },
    ],
  },
  {
    id: '72f58da9-7350-488a-a1ee-c7c0edbfcc99',
    rules: [
      {
        id: '5d0e38c9-1eb3-40a1-b98e-23492ed01956',
        model: {
          name: 'artist.name',
          type: 'text',
          label: 'Artist',
        },
        operator: 'is',
        value: ['Queen'],
      },
    ],
  },
]

const serializedRuleGroups = [
  {
    id: 'c328a77e-3edf-46ed-8c8b-398ec443e6ad',
    rules: [
      {
        id: '72f58da9-7350-488a-a1ee-c7c0edbfcc99',
        model: 'artist.name',
        operator: 'is',
        value: ['Elvis Presley'],
      },
    ],
  },
  {
    id: '72f58da9-7350-488a-a1ee-c7c0edbfcc99',
    rules: [
      {
        id: '5d0e38c9-1eb3-40a1-b98e-23492ed01956',
        model: 'artist.name',
        operator: 'is',
        value: ['Queen'],
      },
    ],
  },
]

describe('playlistStore', () => {
  const h = createHarness()

  it('serializes playlist for storage', () => {
    expect(playlistStore.serializeSmartPlaylistRulesForStorage(ruleGroups)).toEqual(serializedRuleGroups)
  })

  it('sets up a smart playlist with properly unserialized rules', () => {
    const playlist = h.factory('playlist', {
      is_smart: true,
      rules: serializedRuleGroups as unknown as SmartPlaylistRuleGroup[],
    })

    playlistStore.setupSmartPlaylist(playlist)

    expect(playlist.rules).toEqual(ruleGroups)
  })

  it('stores a playlist', async () => {
    const songs = h.factory('song', 3)
    const playlist = h.factory('playlist')
    const folder = h.factory('playlist-folder')
    const postMock = h.mock(http, 'post').mockResolvedValue(playlist)
    h.mock(playlistStore, 'serializeSmartPlaylistRulesForStorage', null)

    await playlistStore.store({
      name: 'New Playlist',
      folder_id: folder.id,
      description: 'Foo',
    }, songs)

    expect(postMock).toHaveBeenCalledWith('playlists', {
      name: 'New Playlist',
      description: 'Foo',
      songs: songs.map(song => song.id),
      folder_id: folder.id,
    })

    expect(playlistStore.state.playlists).toHaveLength(1)
    expect(playlistStore.state.playlists[0]).toEqual(playlist)
  })

  it('deletes a playlist', async () => {
    const playlist = h.factory('playlist')
    const deleteMock = h.mock(http, 'delete')
    playlistStore.state.playlists = [h.factory('playlist'), playlist]

    await playlistStore.delete(playlist)

    expect(deleteMock).toHaveBeenCalledWith(`playlists/${playlist.id}`)
    expect(playlistStore.state.playlists).toHaveLength(1)
    expect(playlistStore.byId(playlist.id)).toBeUndefined()
  })

  it('adds songs to a playlist', async () => {
    const playlist = h.factory('playlist')
    const songs = h.factory('song', 3)
    const postMock = h.mock(http, 'post').mockResolvedValue(playlist)
    const removeMock = h.mock(cache, 'remove')

    await playlistStore.addContent(playlist, songs)

    expect(postMock).toHaveBeenCalledWith(`playlists/${playlist.id}/songs`, {
      songs: songs.map(song => song.id),
    })

    expect(removeMock).toHaveBeenCalledWith(['playlist.songs', playlist.id])
  })

  it('removes songs from a playlist', async () => {
    const playlist = h.factory('playlist')
    const songs = h.factory('song', 3)
    const deleteMock = h.mock(http, 'delete').mockResolvedValue(playlist)
    const removeMock = h.mock(cache, 'remove')

    await playlistStore.removeContent(playlist, songs)

    expect(deleteMock).toHaveBeenCalledWith(`playlists/${playlist.id}/songs`, {
      songs: songs.map(song => song.id),
    })

    expect(removeMock).toHaveBeenCalledWith(['playlist.songs', playlist.id])
  })

  it('does not modify a smart playlist content', async () => {
    const playlist = factory.states('smart')('playlist')
    const postMock = h.mock(http, 'post')

    await playlistStore.addContent(playlist, h.factory('song', 3))
    expect(postMock).not.toHaveBeenCalled()

    await playlistStore.removeContent(playlist, h.factory('song', 3))
    expect(postMock).not.toHaveBeenCalled()
  })

  it('updates a standard playlist', async () => {
    const playlist = h.factory('playlist')
    playlistStore.state.playlists = [playlist]
    const folder = h.factory('playlist-folder')

    const putMock = h.mock(http, 'put').mockResolvedValue(playlist)

    await playlistStore.update(playlist, {
      name: 'Foo',
      description: 'Bar',
      folder_id: folder.id,
    })

    expect(putMock).toHaveBeenCalledWith(`playlists/${playlist.id}`, {
      name: 'Foo',
      description: 'Bar',
      rules: null,
      folder_id: folder.id,
    })

    expect(playlist.name).toBe('Foo')
  })

  it('updates a smart playlist', async () => {
    const playlist = factory.states('smart')('playlist')
    playlistStore.state.playlists = [playlist]
    const rules = h.factory('smart-playlist-rule-group', 2)
    const serializeMock = h.mock(playlistStore, 'serializeSmartPlaylistRulesForStorage', ['Whatever'])
    const putMock = h.mock(http, 'put').mockResolvedValue(playlist)
    const removeMock = h.mock(cache, 'remove')

    await playlistStore.update(playlist, {
      rules,
      name: 'Foo',
      description: 'Bar',
    })

    expect(serializeMock).toHaveBeenCalledWith(rules)

    expect(putMock).toHaveBeenCalledWith(`playlists/${playlist.id}`, {
      name: 'Foo',
      description: 'Bar',
      rules: ['Whatever'],
      folder_id: undefined,
    })

    expect(removeMock).toHaveBeenCalledWith(['playlist.songs', playlist.id])
  })
})
