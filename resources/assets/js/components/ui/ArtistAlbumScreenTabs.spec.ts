import { screen } from '@testing-library/vue'
import { describe, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ArtistAlbumScreenTabs.vue'

describe('ArtistAlbumScreenTabs', () => {
  const h = createHarness()

  it('renders header and default slots', () => {
    h.render(Component, {
      slots: {
        header: '<ul><li>Songs</li><li>Albums</li></ul>',
        default: '<div>Tab content</div>',
      },
    })
    screen.getByText('Songs')
    screen.getByText('Albums')
    screen.getByText('Tab content')
  })
})
