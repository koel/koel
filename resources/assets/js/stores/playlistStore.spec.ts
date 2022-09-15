import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { expect, it } from 'vitest'
import { cache, http } from '@/services'
import { playlistStore } from '.'

const ruleGroups: SmartPlaylistRuleGroup[] = [
  {
    id: 1,
    rules: [
      {
        id: 1,
        model: {
          name: 'artist.name',
          type: 'text',
          label: 'Artist'
        },
        operator: 'is',
        value: ['Elvis Presley']
      }
    ]
  },
  {
    id: 2,
    rules: [
      {
        id: 1,
        model: {
          name: 'artist.name',
          type: 'text',
          label: 'Artist'
        },
        operator: 'is',
        value: ['Queen']
      }
    ]
  }
]

const serializedRuleGroups = [
  {
    id: 1,
    rules: [
      {
        id: 1,
        model: 'artist.name',
        operator: 'is',
        value: ['Elvis Presley']
      }
    ]
  },
  {
    id: 2,
    rules: [
      {
        id: 1,
        model: 'artist.name',
        operator: 'is',
        value: ['Queen']
      }
    ]
  }
]

new class extends UnitTestCase {
  protected test () {
    it('serializes playlist for storage', () => {
      expect(playlistStore.serializeSmartPlaylistRulesForStorage(ruleGroups)).toEqual(serializedRuleGroups)
    })

    it('sets up a smart playlist with properly unserialized rules', () => {
      const playlist = factory<Playlist>('playlist', {
        is_smart: true,
        rules: serializedRuleGroups as SmartPlaylistRuleGroup[]
      })

      playlistStore.setupSmartPlaylist(playlist)

      expect(playlist.rules).toEqual(ruleGroups)
    })

    it('stores a playlist', async () => {
      const songs = factory<Song>('song', 3)
      const playlist = factory<Playlist>('playlist')
      const postMock = this.mock(http, 'post').mockResolvedValue(playlist)
      const serializeMock = this.mock(playlistStore, 'serializeSmartPlaylistRulesForStorage', null)

      await playlistStore.store('New Playlist', songs, [])

      expect(postMock).toHaveBeenCalledWith('playlists', {
        name: 'New Playlist',
        songs: songs.map(song => song.id),
        rules: null
      })

      expect(serializeMock).toHaveBeenCalledWith([])
      expect(playlistStore.state.playlists).toHaveLength(1)
      expect(playlistStore.state.playlists[0]).toEqual(playlist)
    })

    it('deletes a playlist', async () => {
      const playlist = factory<Playlist>('playlist', { id: 12 })
      const deleteMock = this.mock(http, 'delete')
      playlistStore.state.playlists = [factory<Playlist>('playlist'), playlist]

      await playlistStore.delete(playlist)

      expect(deleteMock).toHaveBeenCalledWith('playlists/12')
      expect(playlistStore.state.playlists).toHaveLength(1)
      expect(playlistStore.byId(playlist.id)).toBeUndefined()
    })

    it('adds songs to a playlist', async () => {
      const playlist = factory<Playlist>('playlist', { id: 12 })
      const songs = factory<Song>('song', 3)
      const postMock = this.mock(http, 'post').mockResolvedValue(playlist)
      const removeMock = this.mock(cache, 'remove')

      await playlistStore.addSongs(playlist, songs)

      expect(postMock).toHaveBeenCalledWith('playlists/12/songs', { songs: songs.map(song => song.id) })
      expect(removeMock).toHaveBeenCalledWith(['playlist.songs', 12])
    })

    it('removes songs from a playlist', async () => {
      const playlist = factory<Playlist>('playlist', { id: 12 })
      const songs = factory<Song>('song', 3)
      const deleteMock = this.mock(http, 'delete').mockResolvedValue(playlist)
      const removeMock = this.mock(cache, 'remove')

      await playlistStore.removeSongs(playlist, songs)

      expect(deleteMock).toHaveBeenCalledWith('playlists/12/songs', { songs: songs.map(song => song.id) })
      expect(removeMock).toHaveBeenCalledWith(['playlist.songs', 12])
    })

    it('does not modify a smart playlist content', async () => {
      const playlist = factory.states('smart')<Playlist>('playlist')
      const postMock = this.mock(http, 'post')

      await playlistStore.addSongs(playlist, factory<Song>('song', 3))
      expect(postMock).not.toHaveBeenCalled()

      await playlistStore.removeSongs(playlist, factory<Song>('song', 3))
      expect(postMock).not.toHaveBeenCalled()
    })

    it('updates a standard playlist', async () => {
      const playlist = factory<Playlist>('playlist', { id: 12 })
      const putMock = this.mock(http, 'put').mockResolvedValue(playlist)

      await playlistStore.update(playlist, { name: 'Foo' })

      expect(putMock).toHaveBeenCalledWith('playlists/12', { name: 'Foo', rules: null })
      expect(playlist.name).toBe('Foo')
    })

    it('updates a smart playlist', async () => {
      const playlist = factory.states('smart')<Playlist>('playlist', { id: 12 })
      const rules = factory<SmartPlaylistRuleGroup>('smart-playlist-rule-group', 2)
      const serializeMock = this.mock(playlistStore, 'serializeSmartPlaylistRulesForStorage', ['Whatever'])
      const putMock = this.mock(http, 'put').mockResolvedValue(playlist)
      const removeMock = this.mock(cache, 'remove')

      await playlistStore.update(playlist, { name: 'Foo', rules })

      expect(serializeMock).toHaveBeenCalledWith(rules)
      expect(putMock).toHaveBeenCalledWith('playlists/12', { name: 'Foo', rules: ['Whatever'] })
      expect(removeMock).toHaveBeenCalledWith(['playlist.songs', 12])
    })
  }
}
