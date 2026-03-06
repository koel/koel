import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
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
    expect(screen.getByText('Songs')).toBeTruthy()
    expect(screen.getByText('Albums')).toBeTruthy()
    expect(screen.getByText('Tab content')).toBeTruthy()
  })
})
