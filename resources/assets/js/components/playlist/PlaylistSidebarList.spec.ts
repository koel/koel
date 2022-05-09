import { beforeEach, it } from 'vitest'
import { cleanup } from '@testing-library/vue'
import { mockHelper, render } from '@/__tests__/__helpers__'
import { playlistStore } from '@/stores'
import factory from '@/__tests__/factory'
import PlaylistSidebarList from './PlaylistSidebarList.vue'
import PlaylistSidebarItem from './PlaylistSidebarItem.vue'

beforeEach(() => {
  cleanup()
  mockHelper.restoreAllMocks()
})

it('renders all playlists', () => {
  playlistStore.state.playlists = [
    factory<Playlist>('playlist', { name: 'Foo Playlist' }),
    factory<Playlist>('playlist', { name: 'Bar Playlist' }),
    factory<Playlist>('playlist', { name: 'Smart Playlist', is_smart: true })
  ]

  const { getByText } = render(PlaylistSidebarList, {
      global: {
        stubs: {
          PlaylistSidebarItem
        }
      }
    })

  ;['Favorites', 'Recently Played', 'Foo Playlist', 'Bar Playlist', 'Smart Playlist'].forEach(name => getByText(name))
})

// other functionalities are handled by E2E
