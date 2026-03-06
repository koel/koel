import { describe, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './CreatePlaylistContextMenuButton.vue'

describe('createPlaylistContextMenuButton.vue', () => {
  const h = createHarness()

  it('renders button with correct title', () => {
    h.render(Component)
    screen.getByTitle('Create a new playlist or folder')
  })
})
