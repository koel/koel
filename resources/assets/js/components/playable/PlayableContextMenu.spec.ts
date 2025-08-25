import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import factory from '@/__tests__/factory'
import { arrayify } from '@/utils/helpers'
import { eventBus } from '@/utils/eventBus'
import { screen, waitFor } from '@testing-library/vue'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/QueuePlaybackService'
import { playlistStore } from '@/stores/playlistStore'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { DialogBoxStub, MessageToasterStub } from '@/__tests__/stubs'
import Router from '@/router'
import Component from './PlayableContextMenu.vue'

describe('playableContextMenu.vue', () => {
  const h = createHarness({
    beforeEach: () => queueStore.state.playables = [],
  })

  const renderComponent = async (playables?: MaybeArray<Playable>) => {
    playables = playables ? arrayify(playables) : h.factory('song', 5)

    const rendered = h.render(Component)
    eventBus.emit('PLAYABLE_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent, playables)
    await h.tick(2)

    return {
      ...rendered,
      playables,
    }
  }

  const fillQueue = () => {
    queueStore.state.playables = h.factory('song', 5)
    queueStore.state.playables[2].playback_state = 'Playing'
  }

  it('plays', async () => {
    h.createAudioPlayer()

    const playMock = h.mock(playbackService, 'play')
    const song = h.factory('song', { playback_state: 'Stopped' })
    await renderComponent(song)

    await h.user.click(screen.getByText('Play'))

    expect(playMock).toHaveBeenCalledWith(song)
  })

  it('pauses playback', async () => {
    h.createAudioPlayer()

    const pauseMock = h.mock(playbackService, 'pause')
    await renderComponent(h.factory('song', { playback_state: 'Playing' }))

    await h.user.click(screen.getByText('Pause'))

    expect(pauseMock).toHaveBeenCalled()
  })

  it('resumes playback', async () => {
    h.createAudioPlayer()

    const resumeMock = h.mock(playbackService, 'resume')
    await renderComponent(h.factory('song', { playback_state: 'Paused' }))

    await h.user.click(screen.getByText('Play'))

    expect(resumeMock).toHaveBeenCalled()
  })

  it('goes to album details screen', async () => {
    const goMock = h.mock(Router, 'go')
    const song = h.factory('song')
    await renderComponent(song)

    await h.user.click(screen.getByText('Go to Album'))

    expect(goMock).toHaveBeenCalledWith(`/#/albums/${song.album_id}`)
  })

  it('goes to artist details screen', async () => {
    const goMock = h.mock(Router, 'go')
    const song = h.factory('song')
    await renderComponent(song)

    await h.user.click(screen.getByText('Go to Artist'))

    expect(goMock).toHaveBeenCalledWith(`/#/artists/${song.artist_id}`)
  })

  it('downloads', async () => {
    const downloadMock = h.mock(downloadService, 'fromPlayables')
    const { playables } = await renderComponent()

    await h.user.click(screen.getByText('Download'))

    expect(downloadMock).toHaveBeenCalledWith(playables)
  })

  it('queues', async () => {
    const queueMock = h.mock(queueStore, 'queue')
    const { playables } = await renderComponent()

    await h.user.click(screen.getByText('Queue'))

    expect(queueMock).toHaveBeenCalledWith(playables)
  })

  it('queues after current', async () => {
    fillQueue()
    const queueMock = h.mock(queueStore, 'queueAfterCurrent')
    const { playables } = await renderComponent()

    await h.user.click(screen.getByText('After Current'))

    expect(queueMock).toHaveBeenCalledWith(playables)
  })

  it('queues to bottom', async () => {
    fillQueue()
    const queueMock = h.mock(queueStore, 'queue')
    const { playables } = await renderComponent()

    await h.user.click(screen.getByText('Bottom of Queue'))

    expect(queueMock).toHaveBeenCalledWith(playables)
  })

  it('queues to top', async () => {
    fillQueue()
    const queueMock = h.mock(queueStore, 'queueToTop')
    const { playables } = await renderComponent()

    await h.user.click(screen.getByText('Top of Queue'))

    expect(queueMock).toHaveBeenCalledWith(playables)
  })

  it('removes from queue', async () => {
    fillQueue()
    const removeMock = h.mock(queueStore, 'unqueue')

    await h.router.activateRoute({
      path: '/queue',
      screen: 'Queue',
    })

    const { playables } = await renderComponent()

    await h.user.click(screen.getByText('Remove from Queue'))

    expect(removeMock).toHaveBeenCalledWith(playables)
  })

  it('does not show "Remove from Queue" when not on Queue screen', async () => {
    fillQueue()

    await h.router.activateRoute({
      path: '/songs',
      screen: 'Songs',
    })

    await renderComponent()

    expect(screen.queryByText('Remove from Queue')).toBeNull()
  })

  it('adds to favorites', async () => {
    const likeMock = h.mock(playableStore, 'favorite')
    const { playables } = await renderComponent()

    await h.user.click(screen.getByText('Favorites'))

    expect(likeMock).toHaveBeenCalledWith(playables)
  })

  it('does not have an option to add to favorites for Favorites screen', async () => {
    await h.router.activateRoute({
      path: '/favorites',
      screen: 'Favorites',
    })

    await renderComponent()

    expect(screen.queryByText('Favorites')).toBeNull()
  })

  it('removes from favorites', async () => {
    const unlikeMock = h.mock(playableStore, 'undoFavorite')

    await h.router.activateRoute({
      path: '/favorites',
      screen: 'Favorites',
    })

    const { playables } = await renderComponent()

    await h.user.click(screen.getByText('Remove from Favorites'))

    expect(unlikeMock).toHaveBeenCalledWith(playables)
  })

  it('lists and adds to existing playlist', async () => {
    playlistStore.state.playlists = h.factory('playlist', 3)
    const addMock = h.mock(playlistStore, 'addContent')
    h.mock(MessageToasterStub.value, 'success')
    const { playables } = await renderComponent()

    playlistStore.state.playlists.forEach(playlist => screen.queryByText(playlist.name))

    await h.user.click(screen.getByText(playlistStore.state.playlists[0].name))

    expect(addMock).toHaveBeenCalledWith(playlistStore.state.playlists[0], playables)
  })

  it('does not list smart playlists', async () => {
    playlistStore.state.playlists = h.factory('playlist', 3)
    playlistStore.state.playlists.push(factory.states('smart')('playlist', { name: 'My Smart Playlist' }))

    await renderComponent()

    expect(screen.queryByText('My Smart Playlist')).toBeNull()
  })

  it('removes from playlist', async () => {
    const playlist = h.factory('playlist')
    playlistStore.state.playlists.push(playlist)

    await h.router.activateRoute({
      path: `/playlists/${playlist.id}`,
      screen: 'Playlist',
    }, { id: String(playlist.id) })

    const { playables } = await renderComponent()

    const removeContentMock = h.mock(playlistStore, 'removeContent')
    const emitMock = h.mock(eventBus, 'emit')

    await h.user.click(screen.getByText('Remove from Playlist'))

    await waitFor(() => {
      expect(removeContentMock).toHaveBeenCalledWith(playlist, playables)
      expect(emitMock).toHaveBeenCalledWith('PLAYLIST_CONTENT_REMOVED', playlist, playables)
    })
  })

  it('does not have an option to remove from playlist if not on Playlist screen', async () => {
    await h.router.activateRoute({
      path: '/songs',
      screen: 'Songs',
    })

    await renderComponent()

    expect(screen.queryByText('Remove from Playlist')).toBeNull()
  })

  it('allows edit songs if current user is admin', async () => {
    h.beAdmin()
    const { playables } = await renderComponent()

    // mock after render to ensure that the component is mounted properly
    const emitMock = h.mock(eventBus, 'emit')
    await h.user.click(screen.getByText('Edit…'))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_SONG_FORM', playables)
  })

  it('does not allow edit songs if current user is not admin', async () => {
    h.be()
    await renderComponent()
    expect(screen.queryByText('Edit…')).toBeNull()
  })

  it('has an option to copy shareable URL in Community edition', async () => {
    await renderComponent(h.factory('song'))
    screen.getByText('Copy Shareable URL')
  })

  it('has an option to copy shareable URL if song is public in Plus edition', async () => {
    h.enablePlusEdition()

    await renderComponent(h.factory('song', { is_public: true }))
    screen.getByText('Copy Shareable URL')
  })

  it('does not have an option to share if song is private in Plus edition', async () => {
    h.enablePlusEdition()

    await renderComponent(h.factory('song', { is_public: false }))
    expect(screen.queryByText('Copy Shareable URL')).toBeNull()
  })

  it('deletes song', async () => {
    const confirmMock = h.mock(DialogBoxStub.value, 'confirm', true)
    const toasterMock = h.mock(MessageToasterStub.value, 'success')
    const deleteMock = h.mock(playableStore, 'deleteSongsFromFilesystem')
    h.beAdmin()
    const { playables } = await renderComponent()

    const emitMock = h.mock(eventBus, 'emit')

    await h.user.click(screen.getByText('Delete from Filesystem'))

    await waitFor(() => {
      expect(confirmMock).toHaveBeenCalled()
      expect(deleteMock).toHaveBeenCalledWith(playables)
      expect(toasterMock).toHaveBeenCalledWith('Deleted 5 songs from the filesystem.')
      expect(emitMock).toHaveBeenCalledWith('SONGS_DELETED', playables)
    })
  })

  it('does not have an option to delete songs if current user is not admin', async () => {
    h.be()
    await renderComponent()
    expect(screen.queryByText('Delete from Filesystem')).toBeNull()
  })

  it('creates playlist from selected songs', async () => {
    h.be()
    const { playables } = await renderComponent()

    // mock after render to ensure that the component is mounted properly
    const emitMock = h.mock(eventBus, 'emit')

    await h.user.click(screen.getByText('New Playlist…'))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_CREATE_PLAYLIST_FORM', null, playables)
  })

  it('does not have the options to mark song as private or public in Community edition', async () => {
    await renderComponent(h.factory('song'))
    expect(screen.queryByText('Mark as Private')).toBeNull()
    expect(screen.queryByText('Unmark as Private')).toBeNull()
  })

  it('makes songs private', async () => {
    h.enablePlusEdition()

    const user = h.factory('user')
    const songs = h.factory('song', 5, {
      is_public: true,
      owner_id: user.id,
    })

    h.be(user)

    await renderComponent(songs)
    const privatizeMock = h.mock(playableStore, 'privatizeSongs').mockResolvedValue(songs.map(song => song.id))

    await h.user.click(screen.getByText('Mark as Private'))

    expect(privatizeMock).toHaveBeenCalledWith(songs)
  })

  it('makes songs public', async () => {
    h.enablePlusEdition()

    const user = h.factory('user')
    const songs = h.factory('song', 5, {
      is_public: false,
      owner_id: user.id,
    })

    h.be(user)

    await renderComponent(songs)
    const publicizeMock = h.mock(playableStore, 'publicizeSongs').mockResolvedValue(songs.map(song => song.id))

    await h.user.click(screen.getByText('Unmark as Private'))

    expect(publicizeMock).toHaveBeenCalledWith(songs)
  })

  it('does not have an option to make songs public or private if current user is not owner', async () => {
    h.enablePlusEdition()

    const user = h.factory('user')
    const owner = h.factory('user')
    const songs = h.factory('song', 5, {
      is_public: false,
      owner_id: owner.id,
    })

    h.be(user)

    await renderComponent(songs)

    expect(screen.queryByText('Unmark as Private')).toBeNull()
    expect(screen.queryByText('Mark as Private')).toBeNull()
  })

  it('has both options to make public and private if songs have mixed visibilities', async () => {
    h.enablePlusEdition()

    const owner = h.factory('user')
    const songs = h.factory('song', 2, {
      is_public: false,
      owner_id: owner.id,
    }).concat(...h.factory('song', 3, {
      is_public: true,
      owner_id: owner.id,
    }))

    h.be(owner)
    await renderComponent(songs)

    screen.getByText('Unmark as Private')
    screen.getByText('Mark as Private')
  })

  it('does not have an option to make songs public or private or Community edition', async () => {
    const owner = h.factory('user')
    const songs = h.factory('song', 5, {
      is_public: false,
      owner_id: owner.id,
    })

    h.be(owner)
    await renderComponent(songs)

    expect(screen.queryByText('Unmark as Private')).toBeNull()
    expect(screen.queryByText('Mark as Private')).toBeNull()
  })
})
