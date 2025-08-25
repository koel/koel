import { clone } from 'lodash'
import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { playlistStore } from '@/stores/playlistStore'
import { queueStore } from '@/stores/queueStore'
import { eventBus } from '@/utils/eventBus'
import { arrayify } from '@/utils/helpers'
import { playableStore } from '@/stores/playableStore'
import Btn from '@/components/ui/form/Btn.vue'
import Component from './AddToMenu.vue'

describe('addToMenu.vue', () => {
  const h = createHarness()

  const renderComponent = (customConfig: Partial<AddToMenuConfig> = {}) => {
    const playables = h.factory('song', 5)

    const config: AddToMenuConfig = {
      queue: true,
      favorites: true,
    }

    const rendered = h.render(Component, {
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

  it('renders', () => {
    playlistStore.state.playlists = [
      h.factory('playlist', { name: 'Foo' }),
      h.factory('playlist', { name: 'Bar' }),
      h.factory('playlist', { name: 'Baz' }),
    ]

    expect(renderComponent().html()).toMatchSnapshot()
  })

  it.each<[keyof AddToMenuConfig, string | string[]]>([
    ['queue', ['queue-after-current', 'queue-bottom', 'queue-top', 'queue']],
    ['favorites', 'add-to-favorites'],
  ])('renders disabling %s config', (configKey: keyof AddToMenuConfig, testIds: string | string[]) => {
    renderComponent({ [configKey]: false })
    arrayify(testIds).forEach(id => expect(screen.queryByTestId(id)).toBeNull())
  })

  it.each<[string, string, MethodOf<typeof queueStore>]>([
    ['after current', 'queue-after-current', 'queueAfterCurrent'],
    ['to top', 'queue-top', 'queueToTop'],
    ['to bottom', 'queue-bottom', 'queue'],
  ])('queues songs %s', async (_: string, testId: string, queueMethod: MethodOf<typeof queueStore>) => {
    queueStore.state.playables = h.factory('song', 5)
    queueStore.state.playables[2].playback_state = 'Playing'

    const mock = h.mock(queueStore, queueMethod)
    const { playables } = renderComponent()

    await h.user.click(screen.getByTestId(testId))

    expect(mock).toHaveBeenCalledWith(playables)
  })

  it('adds songs to Favorites', async () => {
    const mock = h.mock(playableStore, 'favorite')
    const { playables } = renderComponent()

    await h.user.click(screen.getByTestId('add-to-favorites'))

    expect(mock).toHaveBeenCalledWith(playables)
  })

  it('adds songs to existing playlist', async () => {
    const mock = h.mock(playlistStore, 'addContent')
    playlistStore.state.playlists = h.factory('playlist', 3)
    const { playables } = renderComponent()

    await h.user.click(screen.getAllByTestId('add-to-playlist')[1])

    expect(mock).toHaveBeenCalledWith(playlistStore.state.playlists[1], playables)
  })

  it('creates playlist from selected songs', async () => {
    const emitMock = h.mock(eventBus, 'emit')
    const { playables } = renderComponent()

    await h.user.click(screen.getByText('New Playlist…'))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_CREATE_PLAYLIST_FORM', null, playables)
  })
})
