import { describe, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './UpNext.vue'

describe('upNext.vue', () => {
  const h = createHarness()

  it('renders playable title and author', () => {
    const song = h.factory('song')

    h.render(Component, {
      props: { playable: song },
    })

    screen.getByText('Up Next')
    screen.getByText(song.title)
    screen.getByText(song.artist_name)
  })
})
