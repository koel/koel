import { clone } from 'lodash'
import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { favoriteStore, playlistStore, queueStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { arrayify, eventBus } from '@/utils'
import Btn from '@/components/ui/form/Btn.vue'
import AddToMenu from './AddToMenu.vue'

let songs: Song[]

const config: AddToMenuConfig = {
  queue: true,
  favorites: true
}

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      playlistStore.state.playlists = [
        factory('playlist', { name: 'Foo' }),
        factory('playlist', { name: 'Bar' }),
        factory('playlist', { name: 'Baz' })
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
      ['to bottom', 'queue-bottom', 'queue']
    ])('queues songs %s', async (_: string, testId: string, queueMethod: MethodOf<typeof queueStore>) => {
      queueStore.state.playables = factory('song', 5)
      queueStore.state.playables[2].playback_state = 'Playing'

      const mock = this.mock(queueStore, queueMethod)
      this.renderComponent()

      await this.user.click(screen.getByTestId(testId))

      expect(mock).toHaveBeenCalledWith(songs)
    })

    it('adds songs to Favorites', async () => {
      const mock = this.mock(favoriteStore, 'like')
      this.renderComponent()

      await this.user.click(screen.getByTestId('add-to-favorites'))

      expect(mock).toHaveBeenCalledWith(songs)
    })

    it('adds songs to existing playlist', async () => {
      const mock = this.mock(playlistStore, 'addContent')
      playlistStore.state.playlists = factory('playlist', 3)
      this.renderComponent()

      await this.user.click(screen.getAllByTestId('add-to-playlist')[1])

      expect(mock).toHaveBeenCalledWith(playlistStore.state.playlists[1], songs)
    })

    it('creates playlist from selected songs', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      this.renderComponent()

      await this.user.click(screen.getByText('New Playlist…'))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_CREATE_PLAYLIST_FORM', null, songs)
    })
  }

  private renderComponent (customConfig: Partial<AddToMenuConfig> = {}) {
    songs = factory('song', 5)

    return this.render(AddToMenu, {
      props: {
        songs,
        config: Object.assign(clone(config), customConfig),
        showing: true
      },
      global: {
        stubs: {
          Btn
        }
      }
    })
  }
}
