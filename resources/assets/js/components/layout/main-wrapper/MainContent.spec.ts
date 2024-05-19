import { ref } from 'vue'
import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { albumStore, preferenceStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { CurrentPlayableKey } from '@/symbols'
import AlbumArtOverlay from '@/components/ui/AlbumArtOverlay.vue'
import MainContent from './MainContent.vue'

new class extends UnitTestCase {
  protected test () {
    it('has a translucent overlay per album', async () => {
      this.mock(albumStore, 'fetchThumbnail').mockResolvedValue('http://test/foo.jpg')

      this.renderComponent()

      await waitFor(() => screen.getByTestId('album-art-overlay'))
    })

    it('does not have a translucent over if configured not so', async () => {
      preferenceStore.state.show_album_art_overlay = false

      this.renderComponent()

      await waitFor(() => expect(screen.queryByTestId('album-art-overlay')).toBeNull())
    })
  }

  private renderComponent () {
    return this.render(MainContent, {
      global: {
        provide: {
          [<symbol>CurrentPlayableKey]: ref(factory<Song>('song'))
        },
        stubs: {
          AlbumArtOverlay,
          AllSongsScreen: this.stub('all-songs-screen'),
          AlbumListScreen: this.stub('album-list-screen'),
          ArtistListScreen: this.stub('artist-list-screen'),
          PlaylistScreen: this.stub('playlist-screen'),
          FavoritesScreen: this.stub('favorites-screen'),
          RecentlyPlayedScreen: this.stub('recently-played-screen'),
          UploadScreen: this.stub('upload-screen'),
          SearchExcerptsScreen: this.stub('search-excerpts-screen'),
          GenreScreen: this.stub('genre-screen'),
          HomeScreen: this.stub(), // so that home overview requests are not made
          Visualizer: this.stub('visualizer')
        }
      }
    })
  }
}
