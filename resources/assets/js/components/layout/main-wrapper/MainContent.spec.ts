import { ref } from 'vue'
import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { albumStore } from '@/stores/albumStore'
import { preferenceStore } from '@/stores/preferenceStore'
import { CurrentStreamableKey } from '@/symbols'
import AlbumArtOverlay from '@/components/ui/AlbumArtOverlay.vue'
import Component from './MainContent.vue'

describe('mainContent.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    return h.render(Component, {
      global: {
        provide: {
          [<symbol>CurrentStreamableKey]: ref(h.factory('song')),
        },
        stubs: {
          AlbumArtOverlay,
          AllSongsScreen: h.stub('all-songs-screen'),
          AlbumListScreen: h.stub('album-list-screen'),
          ArtistListScreen: h.stub('artist-list-screen'),
          PlaylistScreen: h.stub('playlist-screen'),
          FavoritesScreen: h.stub('favorites-screen'),
          RecentlyPlayedScreen: h.stub('recently-played-screen'),
          UploadScreen: h.stub('upload-screen'),
          SearchExcerptsScreen: h.stub('search-excerpts-screen'),
          GenreScreen: h.stub('genre-screen'),
          HomeScreen: h.stub(), // so that home overview requests are not made
          Visualizer: h.stub('visualizer'),
        },
      },
    })
  }

  it('has a translucent overlay per album', async () => {
    h.mock(albumStore, 'fetchThumbnail').mockResolvedValue('http://test/foo.jpg')

    renderComponent()

    await waitFor(() => screen.getByTestId('album-art-overlay'))
  })

  it('does not have a translucent over if configured not so', async () => {
    preferenceStore.state.show_album_art_overlay = false

    renderComponent()

    await waitFor(() => expect(screen.queryByTestId('album-art-overlay')).toBeNull())
  })
})
