import { reactive } from 'vue'
import isMobile from 'ismobilejs'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { authService, http } from '@/services'
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
      expect(songStore.getFormattedLength(factory<Song>('song', { length: 123 }))).toBe('02:03')
      expect(songStore.getFormattedLength([
        factory<Song>('song', { length: 122 }),
        factory<Song>('song', { length: 123 })
      ])).toBe('04:05')
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
            cover: 'http://localhost/removed-album.jpg',
            created_at: '2020-01-01'
          }],
          artists: [{
            id: 42,
            name: 'Removed Artist',
            image: 'http://localhost/removed-artist.jpg',
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
      commonStore.state.cdn_url = 'http://localhost/'
      const song = factory<Song>('song', { id: 'foo' })
      this.mock(authService, 'getToken', 'hadouken')

      expect(songStore.getSourceUrl(song)).toBe('http://localhost/play/foo?api_token=hadouken')

      isMobile.any = true
      preferenceStore.transcodeOnMobile = true
      expect(songStore.getSourceUrl(song)).toBe('http://localhost/play/foo/1/128?api_token=hadouken')
    })

    it('gets shareable URL', () => {
      const song = factory<Song>('song', { id: 'foo' })
      expect(songStore.getShareableUrl(song)).toBe('http://localhost/#!/song/foo')
    })

    it('syncs with the vault', () => {
      const song = factory<Song>('song', {
        playback_state: null
      })

      const trackPlayCountMock = this.mock(songStore, 'setUpPlayCountTracking')

      expect(songStore.syncWithVault(song)).toEqual([reactive(song)])
      expect(songStore.vault.has(song.id)).toBe(true)
      expect(trackPlayCountMock).toHaveBeenCalledOnce()

      expect(songStore.syncWithVault(song)).toEqual([reactive(song)])
      expect(songStore.vault.has(song.id)).toBe(true)
      // second call shouldn't set up play count tracking again
      expect(trackPlayCountMock).toHaveBeenCalledOnce()
    })

    it('sets up play count tracking', async () => {
      const refreshMock = this.mock(overviewStore, 'refresh')
      const artist = reactive(factory<Artist>('artist', { id: 42, play_count: 100 }))
      const album = reactive(factory<Album>('album', { id: 10, play_count: 120 }))
      const albumArtist = reactive(factory<Artist>('artist', { id: 43, play_count: 130 }))

      artistStore.vault.set(42, artist)
      artistStore.vault.set(43, albumArtist)
      albumStore.vault.set(10, album)

      const song = reactive(factory<Song>('song', {
        album_id: 10,
        artist_id: 42,
        album_artist_id: 43,
        play_count: 98
      }))

      songStore.setUpPlayCountTracking(song)
      song.play_count = 100

      await this.tick()

      expect(artist.play_count).toBe(102)
      expect(album.play_count).toBe(122)
      expect(albumArtist.play_count).toBe(132)
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

      await songStore.fetchForPlaylist(playlist)

      expect(getMock).toHaveBeenCalledWith('playlists/42/songs')
      expect(syncMock).toHaveBeenCalledWith(songs)
    })

    it('paginates', async () => {
      const songs = factory<Song>('song', 3)

      const getMock = this.mock(http, 'get').mockResolvedValueOnce({
        data: songs,
        links: {
          next: 'http://localhost/api/v1/songs?page=3'
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
  }
}
