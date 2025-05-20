import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils/eventBus'
import { playbackService } from '@/services/playbackService'
import { queueStore } from '@/stores/queueStore'
import { songStore } from '@/stores/songStore'
import { mediaBrowser } from '@/services/mediaBrowser'
import Router from '@/router'
import Component from './MediaBrowserContextMenu.vue'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => queueStore.state.playables = [])
  }

  protected test () {
    it('opens the folder if the only item is a folder', async () => {
      const folder = factory('folder', { path: 'foo/bar' })
      const items = [folder]
      const goMock = this.mock(Router, 'go')

      await this.renderComponent(items)
      await this.user.click(screen.getByText('Open'))

      expect(goMock).toHaveBeenCalledWith('/#/browse/foo/bar')
    })

    it('plays', async () => {
      const resolvedSongs = factory('song', 3)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      const resolveMock = this.mock(songStore, 'resolveFromMediaReferences').mockResolvedValue(resolvedSongs)
      const goMock = this.mock(Router, 'go')

      // we don't care about the actual references here, as this functionality should have been tested in the
      // mediaBrowser spec
      const extractReferencesMock = this.mock(mediaBrowser, 'extractMediaReferences').mockReturnValue([{
        id: 'foo',
        type: 'song',
      }])

      const items = [...factory('song', 2), factory('folder')]

      await this.renderComponent(items)

      expect(extractReferencesMock).toHaveBeenCalled()

      await this.user.click(screen.getByText('Play'))

      expect(resolveMock).toHaveBeenCalledWith([{
        id: 'foo',
        type: 'song',
      }])

      expect(playMock).toHaveBeenCalledWith(resolvedSongs)
      expect(goMock).toHaveBeenCalledWith('/#/queue')
    })

    it('shuffles', async () => {
      const resolvedSongs = factory('song', 3)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      const resolveMock = this.mock(songStore, 'resolveFromMediaReferences').mockResolvedValue(resolvedSongs)
      const goMock = this.mock(Router, 'go')

      // we don't care about the actual references here, as this functionality should have been tested in the
      // mediaBrowser spec
      const extractReferencesMock = this.mock(mediaBrowser, 'extractMediaReferences').mockReturnValue([{
        id: 'foo',
        type: 'song',
      }])

      const items = [...factory('song', 2), factory('folder')]

      await this.renderComponent(items)

      expect(extractReferencesMock).toHaveBeenCalled()

      await this.user.click(screen.getByText('Shuffle'))

      expect(resolveMock).toHaveBeenCalledWith([{
        id: 'foo',
        type: 'song',
      }], true)

      expect(playMock).toHaveBeenCalledWith(resolvedSongs, true)
      expect(goMock).toHaveBeenCalledWith('/#/queue')
    })

    it('adds to queue', async () => {
      const resolvedSongs = factory('song', 3)
      const queueMock = this.mock(queueStore, 'queueAfterCurrent')
      const resolveMock = this.mock(songStore, 'resolveFromMediaReferences').mockResolvedValue(resolvedSongs)

      // we don't care about the actual references here, as this functionality should have been tested in the
      // mediaBrowser spec
      const extractReferencesMock = this.mock(mediaBrowser, 'extractMediaReferences').mockReturnValue([{
        id: 'foo',
        type: 'song',
      }])

      const items = [...factory('song', 2), factory('folder')]

      await this.renderComponent(items)

      expect(extractReferencesMock).toHaveBeenCalled()

      await this.user.click(screen.getByText('Add to Queue'))

      expect(resolveMock).toHaveBeenCalledWith([{
        id: 'foo',
        type: 'song',
      }])

      expect(queueMock).toHaveBeenCalledWith(resolvedSongs)
    })
  }

  private async renderComponent (items: Array<Song | Folder>) {
    const rendered = this.render(Component)
    eventBus.emit('MEDIA_BROWSER_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent, items)
    await this.tick(2)

    return rendered
  }

  private fillQueue () {
    queueStore.state.playables = factory('song', 5)
    queueStore.state.playables[2].playback_state = 'Playing'
  }
}
