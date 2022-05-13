import { it } from 'vitest'
import { playlistStore } from '@/stores'
import factory from '@/__tests__/factory'
import PlaylistSidebarList from './PlaylistSidebarList.vue'
import PlaylistSidebarItem from './PlaylistSidebarItem.vue'
import UnitTestCase from '@/__tests__/UnitTestCase'

new class extends UnitTestCase {
  protected test () {
    it('renders all playlists', () => {
      playlistStore.state.playlists = [
        factory<Playlist>('playlist', { name: 'Foo Playlist' }),
        factory<Playlist>('playlist', { name: 'Bar Playlist' }),
        factory<Playlist>('playlist', { name: 'Smart Playlist', is_smart: true })
      ]

      const { getByText } = this.render(PlaylistSidebarList, {
          global: {
            stubs: {
              PlaylistSidebarItem
            }
          }
        })

      ;['Favorites', 'Recently Played', 'Foo Playlist', 'Bar Playlist', 'Smart Playlist'].forEach(t => getByText(t))
    })

    // other functionalities are handled by E2E
  }
}
