import { ref } from 'vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { albumStore, preferenceStore } from '@/stores'
import { fireEvent, waitFor } from '@testing-library/vue'
import { ActiveScreenKey } from '@/symbols'
import AlbumListScreen from './AlbumListScreen.vue'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => this.mock(albumStore, 'paginate'))
  }

  private renderComponent () {
    albumStore.state.albums = factory<Album>('album', 9)
    return this.render(AlbumListScreen, {
      global: {
        provide: {
          [<symbol>ActiveScreenKey]: ref('Albums')
        }
      }
    })
  }

  protected test () {
    it('renders', () => {
      expect(this.renderComponent().getAllByTestId('album-card')).toHaveLength(9)
    })

    it.each<[ArtistAlbumViewMode]>([['list'], ['thumbnails']])('sets layout from preferences', async (mode) => {
      preferenceStore.albumsViewMode = mode

      const { getByTestId } = this.renderComponent()

      await waitFor(() => expect(getByTestId('album-list').classList.contains(`as-${mode}`)).toBe(true))
    })

    it('switches layout', async () => {
      const { getByTestId, getByTitle } = this.renderComponent()

      await fireEvent.click(getByTitle('View as list'))
      await waitFor(() => expect(getByTestId('album-list').classList.contains(`as-list`)).toBe(true))

      await fireEvent.click(getByTitle('View as thumbnails'))
      await waitFor(() => expect(getByTestId('album-list').classList.contains(`as-thumbnails`)).toBe(true))
    })
  }
}
