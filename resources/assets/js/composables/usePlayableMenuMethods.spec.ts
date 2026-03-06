import { describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { eventBus } from '@/utils/eventBus'
import { createHarness } from '@/__tests__/TestHarness'

vi.mock('@/composables/usePlaylistContentManagement', () => ({
  usePlaylistContentManagement: () => ({
    addToPlaylist: vi.fn(),
  }),
}))

import { usePlayableMenuMethods } from './usePlayableMenuMethods'

describe('usePlayableMenuMethods', () => {
  const h = createHarness()

  it('queues to bottom', async () => {
    const songs = [h.factory('song')]
    const playables = ref<Playable[]>(songs)
    const close = vi.fn()

    h.mock(queueStore, 'queue')

    const { queueToBottom } = usePlayableMenuMethods(playables, close)
    await queueToBottom()

    expect(close).toHaveBeenCalled()
    expect(queueStore.queue).toHaveBeenCalledWith(songs)
  })

  it('queues to top', async () => {
    const songs = [h.factory('song')]
    const playables = ref<Playable[]>(songs)
    const close = vi.fn()

    h.mock(queueStore, 'queueToTop')

    const { queueToTop } = usePlayableMenuMethods(playables, close)
    await queueToTop()

    expect(queueStore.queueToTop).toHaveBeenCalledWith(songs)
  })

  it('adds to favorites', async () => {
    const songs = [h.factory('song')]
    const playables = ref<Playable[]>(songs)
    const close = vi.fn()

    h.mock(playableStore, 'favorite')

    const { addToFavorites } = usePlayableMenuMethods(playables, close)
    await addToFavorites()

    expect(playableStore.favorite).toHaveBeenCalledWith(songs)
  })

  it('opens new playlist modal', async () => {
    const songs = [h.factory('song')]
    const playables = ref<Playable[]>(songs)
    const close = vi.fn()
    const emitMock = h.mock(eventBus, 'emit')

    const { addToNewPlaylist } = usePlayableMenuMethods(playables, close)
    await addToNewPlaylist()

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_CREATE_PLAYLIST_FORM', null, songs)
  })
})
