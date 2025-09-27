import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import factory from '@/__tests__/factory'
import { MessageToasterStub } from '@/__tests__/stubs'
import { screen, waitFor } from '@testing-library/vue'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { userStore } from '@/stores/userStore'
import { playbackService } from '@/services/QueuePlaybackService'
import { eventBus } from '@/utils/eventBus'
import Router from '@/router'
import { playlistStore } from '@/stores/playlistStore'
import Component from './PlaylistContextMenu.vue'

describe('playlistContextMenu.vue', () => {
  const h = createHarness({
    beforeEach: () => queueStore.state.playables = [],
  })

  const renderComponent = async (playlist: Playlist, user: CurrentUser | null = null) => {
    user = user || h.factory.states('current')('user', {
      id: playlist.owner_id,
    }) as CurrentUser

    userStore.state.current = user

    const rendered = h.render(Component, {
      props: {
        playlist,
      },
    })

    await h.tick(2)

    return {
      ...rendered,
      playlist,
      user,
    }
  }

  it('edits a standard playlist', async () => {
    const { playlist } = await renderComponent(h.factory('playlist'))
    const emitMock = h.mock(eventBus, 'emit')

    await h.user.click(screen.getByText('Edit…'))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist)
  })

  it('edits a smart playlist', async () => {
    const { playlist } = await renderComponent(factory.states('smart')('playlist'))
    const emitMock = h.mock(eventBus, 'emit')

    await h.user.click(screen.getByText('Edit…'))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist)
  })

  it('deletes a playlist', async () => {
    const deleteMock = h.mock(playlistStore, 'delete')
    const { playlist } = await renderComponent(h.factory('playlist'))

    await h.user.click(screen.getByText('Delete'))

    expect(deleteMock).toHaveBeenCalledWith(playlist)
  })

  it('plays', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 3)
    const fetchMock = h.mock(playableStore, 'fetchForPlaylist').mockResolvedValue(songs)
    const queueMock = h.mock(playbackService, 'queueAndPlay')
    const goMock = h.mock(Router, 'go')
    const { playlist } = await renderComponent(h.factory('playlist'))

    await h.user.click(screen.getByText('Play'))

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(playlist)
      expect(queueMock).toHaveBeenCalledWith(songs)
      expect(goMock).toHaveBeenCalledWith('/#/queue')
    })
  })

  it('warns if attempting to play an empty playlist', async () => {
    h.createAudioPlayer()

    const fetchMock = h.mock(playableStore, 'fetchForPlaylist').mockResolvedValue([])
    const queueMock = h.mock(playbackService, 'queueAndPlay')
    const goMock = h.mock(Router, 'go')
    const warnMock = h.mock(MessageToasterStub.value, 'warning')

    const { playlist } = await renderComponent(h.factory('playlist'))

    await h.user.click(screen.getByText('Play'))

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(playlist)
      expect(queueMock).not.toHaveBeenCalled()
      expect(goMock).not.toHaveBeenCalled()
      expect(warnMock).toHaveBeenCalledWith('The playlist is empty.')
    })
  })

  it('shuffles', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 3)
    const fetchMock = h.mock(playableStore, 'fetchForPlaylist').mockResolvedValue(songs)
    const queueMock = h.mock(playbackService, 'queueAndPlay')
    const goMock = h.mock(Router, 'go')
    const { playlist } = await renderComponent(h.factory('playlist'))

    await h.user.click(screen.getByText('Shuffle'))

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(playlist)
      expect(queueMock).toHaveBeenCalledWith(songs, true)
      expect(goMock).toHaveBeenCalledWith('/#/queue')
    })
  })

  it('warns if attempting to shuffle an empty playlist', async () => {
    h.createAudioPlayer()

    const fetchMock = h.mock(playableStore, 'fetchForPlaylist').mockResolvedValue([])
    const queueMock = h.mock(playbackService, 'queueAndPlay')
    const goMock = h.mock(Router, 'go')
    const warnMock = h.mock(MessageToasterStub.value, 'warning')

    const { playlist } = await renderComponent(h.factory('playlist'))

    await h.user.click(screen.getByText('Shuffle'))

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(playlist)
      expect(queueMock).not.toHaveBeenCalled()
      expect(goMock).not.toHaveBeenCalled()
      expect(warnMock).toHaveBeenCalledWith('The playlist is empty.')
    })
  })

  it('queues', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 3)
    const fetchMock = h.mock(playableStore, 'fetchForPlaylist').mockResolvedValue(songs)
    const queueMock = h.mock(queueStore, 'queueAfterCurrent')
    const toastMock = h.mock(MessageToasterStub.value, 'success')
    const { playlist } = await renderComponent(h.factory('playlist'))

    await h.user.click(screen.getByText('Add to Queue'))

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(playlist)
      expect(queueMock).toHaveBeenCalledWith(songs)
      expect(toastMock).toHaveBeenCalledWith('Playlist added to queue.')
    })
  })

  it('does not have an option to edit or delete if the playlist is not owned by the current user', async () => {
    await renderComponent(h.factory('playlist'), h.factory.states('current')('user') as CurrentUser)

    expect(screen.queryByText('Edit…')).toBeNull()
    expect(screen.queryByText('Delete')).toBeNull()
  })

  it('opens collaboration form', async () => await h.withPlusEdition(async () => {
    const { playlist } = await renderComponent(h.factory('playlist'))
    const emitMock = h.mock(eventBus, 'emit')

    await h.user.click(screen.getByText('Collaborate…'))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_PLAYLIST_COLLABORATION', playlist)
  }))

  it('requests the embed form', async () => {
    const { playlist } = await renderComponent(h.factory('playlist'))
    const emitMock = h.mock(eventBus, 'emit')
    await h.user.click(screen.getByText('Embed…'))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_CREATE_EMBED_FORM', playlist)
  })
})
