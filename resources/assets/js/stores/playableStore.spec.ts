import { reactive } from 'vue'
import { describe, expect, it } from 'vitest'
import isMobile from 'ismobilejs'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import { cache } from '@/services/cache'
import { http } from '@/services/http'
import type { SongUpdateResult } from '@/stores/playableStore'
import { playableStore } from '@/stores/playableStore'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { overviewStore } from '@/stores/overviewStore'
import { preferenceStore } from '@/stores/preferenceStore'
import { playlistStore } from '@/stores/playlistStore'

describe('playableStore', () => {
  const h = createHarness({
    afterEach: () => {
      isMobile.any = false
      preferenceStore.temporary.transcode_on_mobile = false
    },
  })

  it('gets a song by ID', () => {
    const song = reactive(h.factory('song', { id: 'foo' }))
    playableStore.vault.set('foo', reactive(song))
    playableStore.vault.set('bar', reactive(h.factory('song', { id: 'bar' })))

    expect(playableStore.byId('foo')).toBe(song)
  })

  it('gets songs by IDs', () => {
    const foo = reactive(h.factory('song', { id: 'foo' }))
    const bar = reactive(h.factory('song', { id: 'bar' }))
    playableStore.vault.set('foo', foo)
    playableStore.vault.set('bar', bar)
    playableStore.vault.set('baz', reactive(h.factory('song', { id: 'baz' })))

    expect(playableStore.byIds(['foo', 'bar'])).toEqual([foo, bar])
  })

  it('gets formatted length', () => {
    expect(playableStore.getFormattedLength(h.factory('song', { length: 123 }))).toBe('2 min 3 sec')
    expect(playableStore.getFormattedLength([
      h.factory('song', { length: 122 }),
      h.factory('song', { length: 123 }),
    ])).toBe('4 min 5 sec')
  })

  it('gets songs by album', () => {
    const songs = reactive(h.factory('song', 2, { album_id: 'iv' }))
    playableStore.vault.set(songs[0].id, songs[0])
    playableStore.vault.set(songs[1].id, songs[1])
    const album = h.factory('album', { id: 'iv' })

    expect(playableStore.byAlbum(album)).toEqual(songs)
  })

  it('resolves a song', async () => {
    const song = h.factory('song')
    const getMock = h.mock(http, 'get').mockResolvedValueOnce(song)

    expect(await playableStore.resolve(song.id)).toEqual(song)
    expect(getMock).toHaveBeenCalledWith(`songs/${song.id}`)

    // next call shouldn't make another request
    expect(await playableStore.resolve(song.id)).toEqual(song)
    expect(getMock).toHaveBeenCalledOnce()
  })

  it('matches a song', () => {
    const song = h.factory('song', { title: 'An amazing song' })
    const songs = [song, ...h.factory('song', 3)]

    expect(playableStore.matchSongsByTitle('An amazing song', songs)).toEqual(song)
    expect(playableStore.matchSongsByTitle('An Amazing Song', songs)).toEqual(song)
  })

  it('registers a play', async () => {
    const song = h.factory('song', { play_count: 42 })

    const postMock = h.mock(http, 'post').mockResolvedValueOnce(h.factory('interaction', {
      song_id: song.id,
      play_count: 50,
    }))

    await playableStore.registerPlay(song)
    expect(postMock).toHaveBeenCalledWith('interaction/play', { song: song.id })
    expect(song.play_count).toBe(50)
  })

  it('scrobbles', async () => {
    const song = h.factory('song')
    song.play_start_time = 123456789
    const postMock = h.mock(http, 'post')

    await playableStore.scrobble(song)

    expect(postMock).toHaveBeenCalledWith(`songs/${song.id}/scrobble`, { timestamp: 123456789 })
  })

  it('updates songs', async () => {
    const songs = h.factory('song', 3)

    const result: SongUpdateResult = {
      songs: h.factory('song', 3),
      albums: h.factory('album', 2),
      artists: h.factory('artist', 2),
      removed: {
        album_ids: ['iv'],
        artist_ids: ['led-zeppelin'],
      },
    }

    const syncSongsMock = h.mock(playableStore, 'syncWithVault')
    const syncAlbumsMock = h.mock(albumStore, 'syncWithVault')
    const syncArtistsMock = h.mock(artistStore, 'syncWithVault')
    const removeAlbumsMock = h.mock(albumStore, 'removeByIds')
    const removeArtistsMock = h.mock(artistStore, 'removeByIds')
    const putMock = h.mock(http, 'put').mockResolvedValueOnce(result)

    await playableStore.updateSongs(songs, {
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
    expect(removeAlbumsMock).toHaveBeenCalledWith(['iv'])
    expect(removeArtistsMock).toHaveBeenCalledWith(['led-zeppelin'])
  })

  it('gets source URL', () => {
    commonStore.state.cdn_url = 'http://test/'
    const song = h.factory('song')
    h.mock(authService, 'getAudioToken', 'hadouken')

    expect(playableStore.getSourceUrl(song)).toBe(`http://test/play/${song.id}?t=hadouken`)

    isMobile.any = true
    preferenceStore.temporary.transcode_on_mobile = true
    expect(playableStore.getSourceUrl(song)).toBe(`http://test/play/${song.id}/1?t=hadouken`)
  })

  it('gets shareable URL', () => {
    const song = h.factory('song')
    expect(playableStore.getShareableUrl(song)).toBe(`http://test/#/songs/${song.id}`)
  })

  it('syncs with the vault', () => {
    const song = h.factory('song', {
      playback_state: null,
    })

    const watchPlayCountMock = h.mock(playableStore, 'watchPlayCount')

    expect(playableStore.syncWithVault(song)).toEqual([reactive(song)])
    expect(playableStore.vault.has(song.id)).toBe(true)
    expect(watchPlayCountMock).toHaveBeenCalledOnce()

    expect(playableStore.syncWithVault(song)).toEqual([reactive(song)])
    expect(playableStore.vault.has(song.id)).toBe(true)
    // second call shouldn't set up play count tracking again
    expect(watchPlayCountMock).toHaveBeenCalledOnce()
  })

  it('watches play count tracking', async () => {
    const refreshMock = h.mock(overviewStore, 'refreshPlayStats')

    const song = reactive(h.factory('song', {
      play_count: 98,
    }))

    playableStore.watchPlayCount(song)
    song.play_count = 100

    await h.tick()

    expect(refreshMock).toHaveBeenCalled()
  })

  it('fetches for album', async () => {
    const songs = h.factory('song', 3)
    const album = h.factory('album')
    const getMock = h.mock(http, 'get').mockResolvedValueOnce(songs)
    const syncMock = h.mock(playableStore, 'syncWithVault', songs)

    await playableStore.fetchSongsForAlbum(album)

    expect(getMock).toHaveBeenCalledWith(`albums/${album.id}/songs`)
    expect(syncMock).toHaveBeenCalledWith(songs)
  })

  it('fetches for artist', async () => {
    const songs = h.factory('song', 3)
    const artist = h.factory('artist')
    const getMock = h.mock(http, 'get').mockResolvedValueOnce(songs)
    const syncMock = h.mock(playableStore, 'syncWithVault', songs)

    await playableStore.fetchSongsForArtist(artist)

    expect(getMock).toHaveBeenCalledWith(`artists/${artist.id}/songs`)
    expect(syncMock).toHaveBeenCalledWith(songs)
  })

  it('fetches for playlist', async () => {
    const songs = h.factory('song', 3)
    const playlist = h.factory('playlist', { id: '966268ea-935d-4f63-a84e-180385376a78' })
    h.mock(playlistStore, 'byId').mockReturnValueOnce(playlist)
    const getMock = h.mock(http, 'get').mockResolvedValueOnce(songs)
    const syncMock = h.mock(playableStore, 'syncWithVault', songs)

    const fetched = await playableStore.fetchForPlaylist(playlist)

    expect(getMock).toHaveBeenCalledWith('playlists/966268ea-935d-4f63-a84e-180385376a78/songs')
    expect(syncMock).toHaveBeenCalledWith(songs)
    expect(fetched).toEqual(songs)
    expect(playlist.playables).toEqual(songs)
  })

  it('fetches for playlist with cache', async () => {
    const songs = h.factory('song', 3)
    const playlist = h.factory('playlist')
    h.mock(playlistStore, 'byId').mockReturnValueOnce(playlist)
    cache.set(['playlist.songs', playlist.id], songs)

    const getMock = h.mock(http, 'get')

    const fetched = await playableStore.fetchForPlaylist(playlist)

    expect(getMock).not.toHaveBeenCalled()
    expect(fetched).toEqual(songs)
    expect(playlist.playables).toEqual(songs)
  })

  it('fetches for playlist discarding cache', async () => {
    const songs = h.factory('song', 3)
    const playlist = h.factory('playlist')
    h.mock(playlistStore, 'byId').mockReturnValueOnce(playlist)
    cache.set(['playlist.songs', playlist.id], songs)

    const getMock = h.mock(http, 'get').mockResolvedValueOnce([])

    await playableStore.fetchForPlaylist(playlist, true)

    expect(getMock).toHaveBeenCalled()
    expect(cache.get(['playlist.songs', playlist.id])).toEqual([])
    expect(playlist.playables).toEqual([])
  })

  it('paginates', async () => {
    const songs = h.factory('song', 3)

    const getMock = h.mock(http, 'get').mockResolvedValueOnce({
      data: songs,
      links: {
        next: 'http://test/api/v1/songs?page=3',
      },
      meta: {
        current_page: 2,
      },
    })

    const syncMock = h.mock(playableStore, 'syncWithVault', reactive(songs))

    expect(await playableStore.paginateSongs({
      page: 2,
      sort: 'title',
      order: 'desc',
    })).toBe(3)

    expect(getMock).toHaveBeenCalledWith('songs?page=2&sort=title&order=desc')
    expect(syncMock).toHaveBeenCalledWith(songs)
    expect(playableStore.state.playables).toEqual(reactive(songs))
  })

  it('paginates for genre', async () => {
    const songs = h.factory('song', 3)
    const reactiveSongs = reactive(songs)

    const getMock = h.mock(http, 'get').mockResolvedValueOnce({
      data: songs,
      links: {
        next: 'http://test/api/v1/songs?page=3',
      },
      meta: {
        current_page: 2,
      },
    })

    const syncMock = h.mock(playableStore, 'syncWithVault', reactiveSongs)

    expect(await playableStore.paginateSongsByGenre('foo', {
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

  it('fetches songs for genre to queue', async () => {
    const songs = h.factory('song', 3)
    const reactiveSongs = reactive(songs)

    const getMock = h.mock(http, 'get').mockResolvedValueOnce(songs)
    const syncMock = h.mock(playableStore, 'syncWithVault', reactiveSongs)

    expect(await playableStore.fetchSongsByGenre('foo')).toEqual(reactiveSongs)

    expect(getMock).toHaveBeenCalledWith('genres/foo/songs/queue?limit=500&random=false')
    expect(syncMock).toHaveBeenCalledWith(songs)
  })

  it('fetches random songs for genre to queue', async () => {
    const songs = h.factory('song', 3)
    const reactiveSongs = reactive(songs)

    const getMock = h.mock(http, 'get').mockResolvedValueOnce(songs)
    const syncMock = h.mock(playableStore, 'syncWithVault', reactiveSongs)

    expect(await playableStore.fetchSongsByGenre('foo', true)).toEqual(reactiveSongs)

    expect(getMock).toHaveBeenCalledWith('genres/foo/songs/queue?limit=500&random=true')
    expect(syncMock).toHaveBeenCalledWith(songs)
  })

  it('fetches favorites', async () => {
    const songs = h.factory('song', 3)
    const getMock = h.mock(http, 'get').mockResolvedValue(songs)

    await playableStore.fetchFavorites()

    expect(getMock).toHaveBeenCalledWith('songs/favorite')
    expect(playableStore.state.favorites).toEqual(songs)
  })

  it('toggles favorite to true', async () => {
    playableStore.state.favorites = h.factory('song', 2, { favorite: true })

    const song = h.factory('song', { favorite: false })

    const postMock = h.mock(http, 'post').mockResolvedValue(h.factory('favorite', {
      favoriteable_type: 'playable',
      favoriteable_id: song.id,
    }))

    await playableStore.toggleFavorite(song)

    expect(postMock).toHaveBeenCalledWith('favorites/toggle', {
      type: 'playable',
      id: song.id,
    })
    expect(song.favorite).toBe(true)
    expect(playableStore.state.favorites).toHaveLength(3)
    expect(playableStore.state.favorites.includes(song)).toBe(true)
  })

  it('toggles favorite to false', async () => {
    playableStore.state.favorites = h.factory('song', 3, { favorite: true })

    const song = playableStore.state.favorites[0]
    const postMock = h.mock(http, 'post').mockResolvedValue(null)

    await playableStore.toggleFavorite(song)

    expect(postMock).toHaveBeenCalledWith('favorites/toggle', {
      type: 'playable',
      id: song.id,
    })

    expect(song.favorite).toBe(false)
    expect(playableStore.state.favorites).toHaveLength(2)
    expect(playableStore.state.favorites).not.toContain(song)
  })

  it('adds to favorites', async () => {
    const songs = h.factory('song', 3)
    const postMock = h.mock(http, 'post')

    await playableStore.favorite(songs)

    expect(postMock).toHaveBeenCalledWith(`favorites`, {
      type: 'playable',
      ids: songs.map(song => song.id),
    })
  })

  it('removes from favorites', async () => {
    const songs = h.factory('song', 3)
    const deleteMock = h.mock(http, 'delete')

    await playableStore.undoFavorite(songs)

    expect(deleteMock).toHaveBeenCalledWith(`favorites`, {
      type: 'playable',
      ids: songs.map(song => song.id),
    })
  })

  it('syncs album properties', () => {
    const album = h.factory('album')
    const songs = h.factory('song', 3, {
      album_id: album.id,
    })

    playableStore.syncWithVault(songs)

    album.name = 'New Album Name'
    album.cover = 'https://test/new-album-cover.jpg'

    playableStore.syncAlbumProperties(album)

    playableStore.byIds<Song>(songs.map(song => song.id))
      .forEach(song => {
        expect(song.album_name).toBe('New Album Name')
        expect(song.album_cover).toBe('https://test/new-album-cover.jpg')
      })
  })

  it('syncs artist properties', () => {
    const artist = h.factory('artist')

    const songsFromArtist = h.factory('song', 3, {
      artist_id: artist.id,
    })

    const songsContributedByArtist = h.factory('song', 2, {
      album_artist_id: artist.id,
    })

    playableStore.syncWithVault([...songsFromArtist, ...songsContributedByArtist])

    artist.name = 'New Artist Name'
    playableStore.syncArtistProperties(artist)

    songsFromArtist.forEach(({ artist_name }) => expect(artist_name).toBe('New Artist Name'))
    songsContributedByArtist.forEach(({ album_artist_name }) => expect(album_artist_name).toBe('New Artist Name'))
  })
})
