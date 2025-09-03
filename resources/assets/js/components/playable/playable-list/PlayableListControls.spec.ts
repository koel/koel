import { screen } from '@testing-library/vue'
import { merge, take } from 'lodash'
import { ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { FilteredPlayablesKey, PlayablesKey, SelectedPlayablesKey } from '@/symbols'
import Component from './PlayableListControls.vue'

describe('playableListControls.vue', () => {
  const h = createHarness()

  const renderComponent = (selectedCount = 1, configOverrides: Partial<PlayableListControlsConfig> = {}) => {
    const songs = h.factory('song', 5)
    const config: PlayableListControlsConfig = merge({
      addTo: {
        queue: true,
        favorites: true,
      },
      clearQueue: true,
      deletePlaylist: true,
      refresh: true,
      filter: true,
    }, configOverrides)

    return h.render(Component, {
      global: {
        provide: {
          [<symbol>PlayablesKey]: [ref(songs)],
          [<symbol>FilteredPlayablesKey]: [ref(songs)],
          [<symbol>SelectedPlayablesKey]: [ref(take(songs, selectedCount))],
        },
      },
      props: {
        config,
      },
    })
  }

  it.each([[0], [1]])('shuffles all if %s songs are selected', async (selectedCount: number) => {
    const { emitted } = renderComponent(selectedCount)

    await h.user.click(screen.getByTitle('Shuffle all. Press Alt/⌥ to change mode.'))

    expect(emitted()['play-all'][0]).toEqual([true])
  })

  it.each([[0], [1]])('plays all if %s songs are selected with Alt pressed', async (selectedCount: number) => {
    const { emitted } = renderComponent(selectedCount)

    await h.user.keyboard('{Alt>}')
    await h.user.click(screen.getByTitle('Play all. Press Alt/⌥ to change mode.'))
    await h.user.keyboard('{/Alt}')

    expect(emitted()['play-all'][0]).toEqual([false])
  })

  it('shuffles selected if more than one song are selected', async () => {
    const { emitted } = renderComponent(2)

    await h.user.click(screen.getByTitle('Shuffle selected. Press Alt/⌥ to change mode.'))

    expect(emitted()['play-selected'][0]).toEqual([true])
  })

  it('plays selected if more than one song are selected with Alt pressed', async () => {
    const { emitted } = renderComponent(2)

    await h.user.keyboard('{Alt>}')
    await h.user.click(screen.getByTitle('Play selected. Press Alt/⌥ to change mode.'))
    await h.user.keyboard('{/Alt}')

    expect(emitted()['play-selected'][0]).toEqual([false])
  })

  it('clears queue', async () => {
    const { emitted } = renderComponent(0)

    await h.user.click(screen.getByTitle('Clear current queue'))

    expect(emitted()['clear-queue']).toBeTruthy()
  })
})
