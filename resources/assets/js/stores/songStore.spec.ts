import { reactive } from 'vue'
import { expect, it } from 'vitest'
import isMobile from 'ismobilejs'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { authService } from '@/services/authService'
import { cache } from '@/services/cache'
import { http } from '@/services/http'
import type { SongUpdateResult } from '@/stores/songStore'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { overviewStore } from '@/stores/overviewStore'
import { preferenceStore } from '@/stores/preferenceStore'
import { playlistStore } from '@/stores/playlistStore'
import { songStore } from '@/stores/songStore'

new class extends UnitTestCase {
  protected afterEach () {
    super.afterEach(() => {
      isMobile.any = false
      preferenceStore.transcode_on_mobile = false
    })
  }

  protected test () {
    it('gets a song by ID', () => {
      const song = reactive(factory('song', { id: 'foo' }))
      songStore.vault.set('foo', reactive(song))
      songStore.vault.set('bar', reactive(factory('song', { id: 'bar' })))

      expect(songStore.byId('foo')).toBe(song)
    })

    it('gets songs by IDs', () => {
      const foo = reactive(factory('song', { id: 'foo' }))
      const bar = reactive(factory('song', { id: 'bar' }))
      songStore.vault.set('foo', foo)
      songStore.vault.set('bar', bar)
      songStore.vault.set('baz', reactive(factory('song', { id: 'baz' })))

      expect(songStore.byIds(['foo', 'bar'])).toEqual([foo, bar])
    })

    it('gets formatted length', () => {
      expect(songStore.getFormattedLength(factory('song', { length: 123 }))).toBe('2 min 3 sec')
      expect(songStore.getFormattedLength([
        factory('song', { length: 122 }),
        factory('song', { length: 123 }),
      ])).toBe('4 min 5 sec')
    })

    it('gets songs by album', () => {
      const songs = reactive(factory('song', 2, { album_id: 3 }))
      songStore.vault.set(songs[0].id, songs[0])
      songStore.vault.set(songs[1].id, songs[1])
      const album = factory('album', { id: 3 })

      expect(songStore.byAlbum(album)).toEqual(songs)
    })

    it('resolves a song', async () => {
      const song = factory('song')
      const getMock = this.mock(http, 'get').mockResolvedValueOnce(song)

      expect(await songStore.resolve(song.id)).toEqual(song)
      expect(getMock).toHaveBeenCalledWith(`songs/${song.id}`)

      // next call shouldn't make another request
      expect(await songStore.resolve(song.id)).toEqual(song)
      expect(getMock).toHaveBeenCalledOnce()
    })

    it('matches a song', () => {
      const song = factory('song', { title: 'An amazing song' })
      const songs = [song, ...factory('song', 3)]

      expect(songStore.match('An amazing song', songs)).toEqual(song)
      expect(songStore.match('An Amazing Song', songs)).toEqual(song)
    })

    it('registers a play', async () => {
      const song = factory('song', { play_count: 42 })

      const postMock = this.mock(http, 'post').mockResolvedValueOnce(factory('interaction', {
        song_id: song.id,
        play_count: 50,
      }))

      await songStore.registerPlay(song)
      expect(postMock).toHaveBeenCalledWith('interaction/play', { song: song.id })
      expect(song.play_count).toBe(50)
    })

    it('scrobbles', async () => {
      const song = factory('song')
      song.play_start_time = 123456789
      const postMock = this.mock(http, 'post')

      await songStore.scrobble(song)

      expect(postMock).toHaveBeenCalledWith(`songs/${song.id}/scrobble`, { timestamp: 123456789 })
    })

    it('updates songs', async () => {
      const songs = factory('song', 3)

      const result: SongUpdateResult = {
        songs: factory('song', 3),
        albums: factory('album', 2),
        artists: factory('artist', 2),
        removed: {
          albums: [{
            id: 10,
            artist_id: 3,
            name: 'Removed Album',
            cover: 'http://test/removed-album.jpg',
            created_at: '2020-01-01',
          }],
          artists: [{
            id: 42,
            name: 'Removed Artist',
            image: 'http://test/removed-artist.jpg',
            created_at: '2020-01-01',
          }],
        },
      }

      const syncSongsMock = this.mock(songStore, 'syncWithVault')
      const syncAlbumsMock = this.mock(albumStore, 'syncWithVault')
      const syncArtistsMock = this.mock(artistStore, 'syncWithVault')
      const removeAlbumsMock = this.mock(albumStore, 'removeByIds')
      const removeArtistsMock = this.mock(artistStore, 'removeByIds')
      const putMock = this.mock(http, 'put').mockResolvedValueOnce(result)

      await songStore.update(songs, {
        album_name: 'Updated Album',
        artist_name: 'Updated Artist',
      })

      expect(putMock).toHaveBeenCalledWith('songs', {
        data: {
          album_name: 'Updated Album',
          artist_name: 'Updated Artist',
        },
        songs: songs.map(song => song.id),
      })

      expect(syncSongsMock).toHaveBeenCalledWith(result.songs)
      expect(syncAlbumsMock).toHaveBeenCalledWith(result.albums)
      expect(syncArtistsMock).toHaveBeenCalledWith(result.artists)
      expect(removeAlbumsMock).toHaveBeenCalledWith([10])
      expect(removeArtistsMock).toHaveBeenCalledWith([42])
    })

    it('gets source URL', () => {
      commonStore.state.cdn_url = 'http://test/'
      const song = factory('song', { id: 'foo' })
      this.mock(authService, 'getAudioToken', 'hadouken')

      expect(songStore.getSourceUrl(song)).toBe('http://test/play/foo?t=hadouken')

      isMobile.any = true
      preferenceStore.transcode_on_mobile = true
      expect(songStore.getSourceUrl(song)).toBe('http://test/play/foo/1?t=hadouken')
    })

    it('gets shareable URL', () => {
      const song = factory('song', { id: 'foo' })
      expect(songStore.getShareableUrl(song)).toBe('http://test/#/song/foo')
    })

    it('syncs with the vault', () => {
      const song = factory('song', {
        playback_state: null,
      })

      const watchPlayCountMock = this.mock(songStore, 'watchPlayCount')

      expect(songStore.syncWithVault(song)).toEqual([reactive(song)])
      expect(songStore.vault.has(song.id)).toBe(true)
      expect(watchPlayCountMock).toHaveBeenCalledOnce()

      expect(songStore.syncWithVault(song)).toEqual([reactive(song)])
      expect(songStore.vault.has(song.id)).toBe(true)
      // second call shouldn't set up play count tracking again
      expect(watchPlayCountMock).toHaveBeenCalledOnce()
    })

    it('watches play count tracking', async () => {
      const refreshMock = this.mock(overviewStore, 'refreshPlayStats')

      const song = reactive(factory('song', {
        album_id: 10,
        artist_id: 42,
        album_artist_id: 43,
        play_count: 98,
      }))

      songStore.watchPlayCount(song)
      song.play_count = 100

      await this.tick()

      expect(refreshMock).toHaveBeenCalled()
    })

    it('fetches for album', async () => {
      const songs = factory('song', 3)
      const album = factory('album', { id: 42 })
      const getMock = this.mock(http, 'get').mockResolvedValueOnce(songs)
      const syncMock = this.mock(songStore, 'syncWithVault', songs)

      await songStore.fetchForAlbum(album)

      expect(getMock).toHaveBeenCalledWith('albums/42/songs')
      expect(syncMock).toHaveBeenCalledWith(songs)
    })

    it('fetches for artist', async () => {
      const songs = factory('song', 3)
      const artist = factory('artist', { id: 42 })
      const getMock = this.mock(http, 'get').mockResolvedValueOnce(songs)
      const syncMock = this.mock(songStore, 'syncWithVault', songs)

      await songStore.fetchForArtist(artist)

      expect(getMock).toHaveBeenCalledWith('artists/42/songs')
      expect(syncMock).toHaveBeenCalledWith(songs)
    })

    it('fetches for playlist', async () => {
      const songs = factory('song', 3)
      const playlist = factory('playlist', { id: '966268ea-935d-4f63-a84e-180385376a78' })
      this.mock(playlistStore, 'byId').mockReturnValueOnce(playlist)
      const getMock = this.mock(http, 'get').mockResolvedValueOnce(songs)
      const syncMock = this.mock(songStore, 'syncWithVault', songs)

      const fetched = await songStore.fetchForPlaylist(playlist)

      expect(getMock).toHaveBeenCalledWith('playlists/966268ea-935d-4f63-a84e-180385376a78/songs')
      expect(syncMock).toHaveBeenCalledWith(songs)
      expect(fetched).toEqual(songs)
      expect(playlist.playables).toEqual(songs)
    })

    it('fetches for playlist with cache', async () => {
      const songs = factory('song', 3)
      const playlist = factory('playlist', { id: '966268ea-935d-4f63-a84e-180385376a78' })
      this.mock(playlistStore, 'byId').mockReturnValueOnce(playlist)
      cache.set(['playlist.songs', playlist.id], songs)

      const getMock = this.mock(http, 'get')

      const fetched = await songStore.fetchForPlaylist(playlist)

      expect(getMock).not.toHaveBeenCalled()
      expect(fetched).toEqual(songs)
      expect(playlist.playables).toEqual(songs)
    })

    it('fetches for playlist discarding cache', async () => {
      const songs = factory('song', 3)
      const playlist = factory('playlist', { id: '966268ea-935d-4f63-a84e-180385376a78' })
      this.mock(playlistStore, 'byId').mockReturnValueOnce(playlist)
      cache.set(['playlist.songs', playlist.id], songs)

      const getMock = this.mock(http, 'get').mockResolvedValueOnce([])

      await songStore.fetchForPlaylist(playlist, true)

      expect(getMock).toHaveBeenCalled()
      expect(cache.get(['playlist.songs', playlist.id])).toEqual([])
      expect(playlist.playables).toEqual([])
    })

    it('paginates', async () => {
      const songs = factory('song', 3)

      const getMock = this.mock(http, 'get').mockResolvedValueOnce({
        data: songs,
        links: {
          next: 'http://test/api/v1/songs?page=3',
        },
        meta: {
          current_page: 2,
        },
      })

      const syncMock = this.mock(songStore, 'syncWithVault', reactive(songs))

      expect(await songStore.paginate({
        page: 2,
        sort: 'title',
        order: 'desc',
        own_songs_only: true,
      })).toBe(3)

      expect(getMock).toHaveBeenCalledWith('songs?page=2&sort=title&order=desc&own_songs_only=true')
      expect(syncMock).toHaveBeenCalledWith(songs)
      expect(songStore.state.songs).toEqual(reactive(songs))
    })

    it('paginates for genre', async () => {
      const songs = factory('song', 3)
      const reactiveSongs = reactive(songs)

      const getMock = this.mock(http, 'get').mockResolvedValueOnce({
        data: songs,
        links: {
          next: 'http://test/api/v1/songs?page=3',
        },
        meta: {
          current_page: 2,
        },
      })

      const syncMock = this.mock(songStore, 'syncWithVault', reactiveSongs)

      expect(await songStore.paginateForGenre('foo', {
        page: 2,
        sort: 'title',
        order: 'desc',
      })).toEqual({
        songs: reactiveSongs,
        nextPage: 3,
      })

      expect(getMock).toHaveBeenCalledWith('genres/foo/songs?page=2&sort=title&order=desc')
      expect(syncMock).toHaveBeenCalledWith(songs)
    })

    it('fetches random songs for genre', async () => {
      const songs = factory('song', 3)
      const reactiveSongs = reactive(songs)

      const getMock = this.mock(http, 'get').mockResolvedValueOnce(songs)
      const syncMock = this.mock(songStore, 'syncWithVault', reactiveSongs)

      expect(await songStore.fetchRandomForGenre('foo')).toEqual(reactiveSongs)

      expect(getMock).toHaveBeenCalledWith('genres/foo/songs/random?limit=500')
      expect(syncMock).toHaveBeenCalledWith(songs)
    })
  }
}
