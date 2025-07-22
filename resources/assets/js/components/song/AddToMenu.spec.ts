import { clone } from 'lodash'
import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { playlistStore } from '@/stores/playlistStore'
import { queueStore } from '@/stores/queueStore'
import { eventBus } from '@/utils/eventBus'
import { arrayify } from '@/utils/helpers'
import { songStore } from '@/stores/songStore'
import Btn from '@/components/ui/form/Btn.vue'
import Component from './AddToMenu.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      playlistStore.state.playlists = [
        factory('playlist', { name: 'Foo' }),
        factory('playlist', { name: 'Bar' }),
        factory('playlist', { name: 'Baz' }),
      ]

      expect(this.renderComponent().html()).toMatchSnapshot()
    })

    it.each<[keyof AddToMenuConfig, string | string[]]>([
      ['queue', ['queue-after-current', 'queue-bottom', 'queue-top', 'queue']],
      ['favorites', 'add-to-favorites'],
    ])('renders disabling %s config', (configKey: keyof AddToMenuConfig, testIds: string | string[]) => {
      this.renderComponent({ [configKey]: false })
      arrayify(testIds).forEach(id => expect(screen.queryByTestId(id)).toBeNull())
    })

    it.each<[string, string, MethodOf<typeof queueStore>]>([
      ['after current', 'queue-after-current', 'queueAfterCurrent'],
      ['to top', 'queue-top', 'queueToTop'],
      ['to bottom', 'queue-bottom', 'queue'],
    ])('queues songs %s', async (_: string, testId: string, queueMethod: MethodOf<typeof queueStore>) => {
      queueStore.state.playables = factory('song', 5)
      queueStore.state.playables[2].playback_state = 'Playing'

      const mock = this.mock(queueStore, queueMethod)
      const { playables } = this.renderComponent()

      await this.user.click(screen.getByTestId(testId))

      expect(mock).toHaveBeenCalledWith(playables)
    })

    it('adds songs to Favorites', async () => {
      const mock = this.mock(songStore, 'favorite')
      const { playables } = this.renderComponent()

      await this.user.click(screen.getByTestId('add-to-favorites'))

      expect(mock).toHaveBeenCalledWith(playables)
    })

    it('adds songs to existing playlist', async () => {
      const mock = this.mock(playlistStore, 'addContent')
      playlistStore.state.playlists = factory('playlist', 3)
      const { playables } = this.renderComponent()

      await this.user.click(screen.getAllByTestId('add-to-playlist')[1])

      expect(mock).toHaveBeenCalledWith(playlistStore.state.playlists[1], playables)
    })

    it('creates playlist from selected songs', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      const { playables } = this.renderComponent()

      await this.user.click(screen.getByText('New Playlist…'))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_CREATE_PLAYLIST_FORM', null, playables)
    })
  }

  private renderComponent (customConfig: Partial<AddToMenuConfig> = {}) {
    const playables = factory('song', 5)

    const config: AddToMenuConfig = {
      queue: true,
      favorites: true,
    }

    const rendered = this.render(Component, {
      props: {
        playables,
        config: Object.assign(clone(config), customConfig),
        showing: true,
      },
      global: {
        stubs: {
          Btn,
        },
      },
    })

    return {
      ...rendered,
      playables,
    }
  }
}
