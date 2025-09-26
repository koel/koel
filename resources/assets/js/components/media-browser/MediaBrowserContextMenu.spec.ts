import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/QueuePlaybackService'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { mediaBrowser } from '@/services/mediaBrowser'
import Router from '@/router'
import Component from './MediaBrowserContextMenu.vue'

describe('mediaBrowserContextMenu.vue', () => {
  const h = createHarness({
    beforeEach: () => queueStore.state.playables = [],
  })

  const renderComponent = (items: Array<Song | Folder>) => {
    return h.render(Component, {
      props: {
        items,
      },
    })
  }

  it('opens the folder if the only item is a folder', async () => {
    const folder = h.factory('folder', { path: 'foo/bar' })
    const items = [folder]
    const goMock = h.mock(Router, 'go')

    await renderComponent(items)
    await h.user.click(screen.getByText('Open'))

    expect(goMock).toHaveBeenCalledWith('/#/browse/foo/bar')
  })

  it('plays', async () => {
    h.createAudioPlayer()

    const resolvedSongs = h.factory('song', 3)
    const playMock = h.mock(playbackService, 'queueAndPlay')
    const resolveMock = h.mock(playableStore, 'resolveSongsFromMediaReferences').mockResolvedValue(resolvedSongs)
    const goMock = h.mock(Router, 'go')

    // we don't care about the actual references here, as this functionality should have been tested in the
    // mediaBrowser spec
    const extractReferencesMock = h.mock(mediaBrowser, 'extractMediaReferences').mockReturnValue([{
      id: 'foo',
      type: 'song',
    }])

    const items = [...h.factory('song', 2), h.factory('folder')]

    renderComponent(items)

    expect(extractReferencesMock).toHaveBeenCalled()

    await h.user.click(screen.getByText('Play'))

    expect(resolveMock).toHaveBeenCalledWith([{
      id: 'foo',
      type: 'song',
    }])

    expect(playMock).toHaveBeenCalledWith(resolvedSongs)
    expect(goMock).toHaveBeenCalledWith('/#/queue')
  })

  it('shuffles', async () => {
    h.createAudioPlayer()

    const resolvedSongs = h.factory('song', 3)
    const playMock = h.mock(playbackService, 'queueAndPlay')
    const resolveMock = h.mock(playableStore, 'resolveSongsFromMediaReferences').mockResolvedValue(resolvedSongs)
    const goMock = h.mock(Router, 'go')

    // we don't care about the actual references here, as this functionality should have been tested in the
    // mediaBrowser spec
    const extractReferencesMock = h.mock(mediaBrowser, 'extractMediaReferences').mockReturnValue([{
      id: 'foo',
      type: 'song',
    }])

    const items = [...h.factory('song', 2), h.factory('folder')]

    renderComponent(items)

    expect(extractReferencesMock).toHaveBeenCalled()

    await h.user.click(screen.getByText('Shuffle'))

    expect(resolveMock).toHaveBeenCalledWith([{
      id: 'foo',
      type: 'song',
    }], true)

    expect(playMock).toHaveBeenCalledWith(resolvedSongs, true)
    expect(goMock).toHaveBeenCalledWith('/#/queue')
  })

  it('adds to queue', async () => {
    const resolvedSongs = h.factory('song', 3)
    const queueMock = h.mock(queueStore, 'queue')
    const resolveMock = h.mock(playableStore, 'resolveSongsFromMediaReferences').mockResolvedValue(resolvedSongs)

    // we don't care about the actual references here, as this functionality should have been tested in the
    // mediaBrowser spec
    const extractReferencesMock = h.mock(mediaBrowser, 'extractMediaReferences').mockReturnValue([{
      id: 'foo',
      type: 'song',
    }])

    const items = [...h.factory('song', 2), h.factory('folder')]

    renderComponent(items)

    expect(extractReferencesMock).toHaveBeenCalled()

    await h.user.click(screen.getByText('Add to Queue'))

    expect(resolveMock).toHaveBeenCalledWith([{
      id: 'foo',
      type: 'song',
    }])

    expect(queueMock).toHaveBeenCalledWith(resolvedSongs)
  })
})
