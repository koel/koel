import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenModal } from '@/__tests__/assertions'
import factory from '@/__tests__/factory'
import { MessageToasterStub } from '@/__tests__/stubs'
import { screen, waitFor } from '@testing-library/vue'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { userStore } from '@/stores/userStore'
import { playbackService } from '@/services/QueuePlaybackService'
import Router from '@/router'
import { playlistStore } from '@/stores/playlistStore'
import EditPlaylistForm from '@/components/playlist/EditPlaylistForm.vue'
import EditSmartPlaylistForm from '@/components/playlist/smart-playlist/EditSmartPlaylistForm.vue'
import PlaylistCollaborationModal from '@/components/playlist/PlaylistCollaborationModal.vue'
import CreateEmbedForm from '@/components/embed/CreateEmbedForm.vue'

const openModalMock = vi.fn()

vi.mock('@/composables/useModal', () => ({
  useModal: () => ({
    openModal: openModalMock,
  }),
}))

import Component from './PlaylistContextMenu.vue'

describe('playlistContextMenu.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      queueStore.state.playables = []
      openModalMock.mockClear()
    },
  })

  const renderComponent = async (playlist: Playlist, user: CurrentUser | null = null) => {
    if (!vi.isMockFunction(playableStore.fetchForPlaylist)) {
      h.mock(playableStore, 'fetchForPlaylist').mockResolvedValue([])
    }

    user =
      user ||
      (h.factory.states('current')('user', {
        id: playlist.owner_id,
      }) as CurrentUser)

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

    await h.user.click(screen.getByText('Edit…'))

    await assertOpenModal(openModalMock, EditPlaylistForm, { playlist })
  })

  it('edits a smart playlist', async () => {
    const { playlist } = await renderComponent(factory.states('smart')('playlist'))

    await h.user.click(screen.getByText('Edit…'))

    await assertOpenModal(openModalMock, EditSmartPlaylistForm, { playlist })
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

  it('opens collaboration form', async () =>
    await h.withPlusEdition(async () => {
      const { playlist } = await renderComponent(h.factory('playlist'))

      await h.user.click(screen.getByText('Collaborate…'))

      await assertOpenModal(openModalMock, PlaylistCollaborationModal, { playlist })
    }))

  it('requests the embed form', async () => {
    const { playlist } = await renderComponent(h.factory('playlist'))
    await h.user.click(screen.getByText('Embed…'))

    await assertOpenModal(openModalMock, CreateEmbedForm, { embeddable: playlist })
  })
})
