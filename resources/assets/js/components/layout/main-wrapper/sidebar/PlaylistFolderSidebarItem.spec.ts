import { describe, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PlaylistFolderSidebarItem.vue'

describe('playlistFolderSidebarItem.vue', () => {
  const h = createHarness()

  it('renders folder name', () => {
    const folder = h.factory('playlist-folder')

    h.render(Component, {
      props: { folder },
    })

    screen.getByText(folder.name)
  })
})
