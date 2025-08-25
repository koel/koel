import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import Component from './MediaListItem.vue'

describe('mediaListItem.vue', () => {
  const h = createHarness()

  it('renders a playable', async () => {
    const item = h.factory('song', {
      basename: 'whatever.mp3',
    })

    const { emitted } = h.render(Component, {
      props: {
        item,
      },
    })

    await h.user.click(screen.getByTitle('Play'))

    expect(emitted()['play-song']).toBeTruthy()
  })

  it('renders a folder', async () => {
    const item = h.factory('folder')

    const { emitted } = h.render(Component, {
      props: {
        item,
      },
    })

    await h.user.click(screen.getByTitle('Open'))

    expect(emitted()['open-folder']).toBeTruthy()
  })
})
