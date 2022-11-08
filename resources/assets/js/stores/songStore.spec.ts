import { reactive } from 'vue'
import isMobile from 'ismobilejs'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { authService, cache, http } from '@/services'
import { albumStore, artistStore, commonStore, overviewStore, preferenceStore, songStore, SongUpdateResult } from '.'

new class extends UnitTestCase {
  protected afterEach () {
    super.afterEach(() => {
      isMobile.any = false
      preferenceStore.transcodeOnMobile = false
    })
  }

  protected test () {
    it('gets a song by ID', () => {
      const song = reactive(factory<Song>('song', { id: 'foo' }))
      songStore.vault.set('foo', reactive(song))
      songStore.vault.set('bar', reactive(factory<Song>('song', { id: 'bar' })))

      expect(songStore.byId('foo')).toBe(song)
    })

    it('gets songs by IDs', () => {
      const foo = reactive(factory<Song>('song', { id: 'foo' }))
      const bar = reactive(factory<Song>('song', { id: 'bar' }))
      songStore.vault.set('foo', foo)
      songStore.vault.set('bar', bar)
      songStore.vault.set('baz', reactive(factory<Song>('song', { id: 'baz' })))

      expect(songStore.byIds(['foo', 'bar'])).toEqual([foo, bar])
    })

    it('gets formatted length', () => {
      expect(songStore.getFormattedLength(factory<Song>('song', { length: 123 }))).toBe('2 min 3 sec')
      expect(songStore.getFormattedLength([
        factory<Song>('song', { length: 122 }),
        factory<Song>('song', { length: 123 })
      ])).toBe('4 min 5 sec')
    })

    it('gets songs by album', () => {
      const songs = reactive(factory<Song>('song', 2, { album_id: 3 }))
      songStore.vault.set(songs[0].id, songs[0])
      songStore.vault.set(songs[1].id, songs[1])
      const album = factory<Album>('album', { id: 3 })

      expect(songStore.byAlbum(album)).toEqual(songs)
    })

    it('resolves a song', async () => {
      const song = factory<Song>('song')
      const getMock = this.mock(http, 'get').mockResolvedValueOnce(song)

      expect(await songStore.resolve(song.id)).toEqual(song)
      expect(getMock).toHaveBeenCalledWith(`songs/${song.id}`)

      // next call shouldn't make another request
      expect(await songStore.resolve(song.id)).toEqual(song)
      expect(getMock).toHaveBeenCalledOnce()
    })

    it('matches a song', () => {
      const song = factory<Song>('song', { title: 'An amazing song' })
      const songs = [song, ...factory<Song>('song', 3)]

      expect(songStore.match('An amazing song', songs)).toEqual(song)
      expect(songStore.match('An Amazing Song', songs)).toEqual(song)
    })

    it('registers a play', async () => {
      const song = factory<Song>('song', { play_count: 42 })

      const postMock = this.mock(http, 'post').mockResolvedValueOnce(factory<Interaction>('interaction', {
        song_id: song.id,
        play_count: 50
      }))

      await songStore.registerPlay(song)
      expect(postMock).toHaveBeenCalledWith('interaction/play', { song: song.id })
      expect(song.play_count).toBe(50)
    })

    it('scrobbles', async () => {
      const song = factory<Song>('song')
      song.play_start_time = 123456789
      const postMock = this.mock(http, 'post')

      await songStore.scrobble(song)

      expect(postMock).toHaveBeenCalledWith(`songs/${song.id}/scrobble`, { timestamp: 123456789 })
    })

    it('updates songs', async () => {
      const songs = factory<Song>('song', 3)

      const result: SongUpdateResult = {
        songs: factory<Song>('song', 3),
        albums: factory<Album>('album', 2),
        artists: factory<Artist>('artist', 2),
        removed: {
          albums: [{
            id: 10,
            artist_id: 3,
            name: 'Removed Album',
            cover: 'http://test/removed-album.jpg',
            created_at: '2020-01-01'
          }],
          artists: [{
            id: 42,
            name: 'Removed Artist',
            image: 'http://test/removed-artist.jpg',
            created_at: '2020-01-01'
          }]
        }
      }

      const syncSongsMock = this.mock(songStore, 'syncWithVault')
      const syncAlbumsMock = this.mock(albumStore, 'syncWithVault')
      const syncArtistsMock = this.mock(artistStore, 'syncWithVault')
      const removeAlbumsMock = this.mock(albumStore, 'removeByIds')
      const removeArtistsMock = this.mock(artistStore, 'removeByIds')
      const putMock = this.mock(http, 'put').mockResolvedValueOnce(result)

      await songStore.update(songs, {
        album_name: 'Updated Album',
        artist_name: 'Updated Artist'
      })

      expect(putMock).toHaveBeenCalledWith('songs', {
        data: {
          album_name: 'Updated Album',
          artist_name: 'Updated Artist'
        },
        songs: songs.map(song => song.id)
      })

      expect(syncSongsMock).toHaveBeenCalledWith(result.songs)
      expect(syncAlbumsMock).toHaveBeenCalledWith(result.albums)
      expect(syncArtistsMock).toHaveBeenCalledWith(result.artists)
      expect(removeAlbumsMock).toHaveBeenCalledWith([10])
      expect(removeArtistsMock).toHaveBeenCalledWith([42])
    })

    it('gets source URL', () => {
      commonStore.state.cdn_url = 'http://test/'
      const song = factory<Song>('song', { id: 'foo' })
      this.mock(authService, 'getToken', 'hadouken')

      expect(songStore.getSourceUrl(song)).toBe('http://test/play/foo?api_token=hadouken')

      isMobile.any = true
      preferenceStore.transcodeOnMobile = true
      expect(songStore.getSourceUrl(song)).toBe('http://test/play/foo/1/128?api_token=hadouken')
    })

    it('gets shareable URL', () => {
      const song = factory<Song>('song', { id: 'foo' })
      expect(songStore.getShareableUrl(song)).toBe('http://test/#/song/foo')
    })

    it('syncs with the vault', () => {
      const song = factory<Song>('song', {
        playback_state: null
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
      const refreshMock = this.mock(overviewStore, 'refresh')

      const song = reactive(factory<Song>('song', {
        album_id: 10,
        artist_id: 42,
        album_artist_id: 43,
        play_count: 98
      }))

      songStore.watchPlayCount(song)
      song.play_count = 100

      await this.tick()

      expect(refreshMock).toHaveBeenCalled()
    })

    it('fetches for album', async () => {
      const songs = factory<Song>('song', 3)
      const album = factory<Album>('album', { id: 42 })
      const getMock = this.mock(http, 'get').mockResolvedValueOnce(songs)
      const syncMock = this.mock(songStore, 'syncWithVault', songs)

      await songStore.fetchForAlbum(album)

      expect(getMock).toHaveBeenCalledWith('albums/42/songs')
      expect(syncMock).toHaveBeenCalledWith(songs)
    })

    it('fetches for artist', async () => {
      const songs = factory<Song>('song', 3)
      const artist = factory<Artist>('artist', { id: 42 })
      const getMock = this.mock(http, 'get').mockResolvedValueOnce(songs)
      const syncMock = this.mock(songStore, 'syncWithVault', songs)

      await songStore.fetchForArtist(artist)

      expect(getMock).toHaveBeenCalledWith('artists/42/songs')
      expect(syncMock).toHaveBeenCalledWith(songs)
    })

    it('fetches for playlist', async () => {
      const songs = factory<Song>('song', 3)
      const playlist = factory<Playlist>('playlist', { id: 42 })
      const getMock = this.mock(http, 'get').mockResolvedValueOnce(songs)
      const syncMock = this.mock(songStore, 'syncWithVault', songs)

      const fetched = await songStore.fetchForPlaylist(playlist)

      expect(getMock).toHaveBeenCalledWith('playlists/42/songs')
      expect(syncMock).toHaveBeenCalledWith(songs)
      expect(fetched).toEqual(songs)
    })

    it('fetches for playlist with cache', async () => {
      const songs = factory<Song>('song', 3)
      const playlist = factory<Playlist>('playlist', { id: 42 })
      cache.set(['playlist.songs', playlist.id], songs)

      const getMock = this.mock(http, 'get')

      const fetched = await songStore.fetchForPlaylist(playlist)

      expect(getMock).not.toHaveBeenCalled()
      expect(fetched).toEqual(songs)
    })

    it('fetches for playlist discarding cache', async () => {
      const songs = factory<Song>('song', 3)
      const playlist = factory<Playlist>('playlist', { id: 42 })
      cache.set(['playlist.songs', playlist.id], songs)

      const getMock = this.mock(http, 'get').mockResolvedValueOnce([])

      await songStore.fetchForPlaylist(playlist, true)

      expect(getMock).toHaveBeenCalled()
      expect(cache.get(['playlist.songs', playlist.id])).toEqual([])
    })

    it('paginates', async () => {
      const songs = factory<Song>('song', 3)

      const getMock = this.mock(http, 'get').mockResolvedValueOnce({
        data: songs,
        links: {
          next: 'http://test/api/v1/songs?page=3'
        },
        meta: {
          current_page: 2
        }
      })

      const syncMock = this.mock(songStore, 'syncWithVault', reactive(songs))

      expect(await songStore.paginate('title', 'desc', 2)).toBe(3)

      expect(getMock).toHaveBeenCalledWith('songs?page=2&sort=title&order=desc')
      expect(syncMock).toHaveBeenCalledWith(songs)
      expect(songStore.state.songs).toEqual(reactive(songs))
    })

    it('paginates for genre', async () => {
      const songs = factory<Song>('song', 3)
      const reactiveSongs = reactive(songs)

      const getMock = this.mock(http, 'get').mockResolvedValueOnce({
        data: songs,
        links: {
          next: 'http://test/api/v1/songs?page=3'
        },
        meta: {
          current_page: 2
        }
      })

      const syncMock = this.mock(songStore, 'syncWithVault', reactiveSongs)

      expect(await songStore.paginateForGenre('foo', 'title', 'desc', 2)).toEqual({
        songs: reactiveSongs,
        nextPage: 3
      })

      expect(getMock).toHaveBeenCalledWith('genres/foo/songs?page=2&sort=title&order=desc')
      expect(syncMock).toHaveBeenCalledWith(songs)
    })

    it('fetches random songs for genre', async () => {
      const songs = factory<Song>('song', 3)
      const reactiveSongs = reactive(songs)

      const getMock = this.mock(http, 'get').mockResolvedValueOnce(songs)
      const syncMock = this.mock(songStore, 'syncWithVault', reactiveSongs)

      expect(await songStore.fetchRandomForGenre('foo')).toEqual(reactiveSongs)

      expect(getMock).toHaveBeenCalledWith('genres/foo/songs/random?limit=500')
      expect(syncMock).toHaveBeenCalledWith(songs)
    })
  }
}
