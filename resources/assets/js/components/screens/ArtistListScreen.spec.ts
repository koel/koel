import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { artistStore, commonStore, preferenceStore } from '@/stores'
import { screen, waitFor } from '@testing-library/vue'
import ArtistListScreen from './ArtistListScreen.vue'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => this.mock(artistStore, 'paginate'))
  }

  protected test () {
    it('renders', async () => {
      await this.renderComponent()
      expect(screen.getAllByTestId('artist-card')).toHaveLength(9)
    })

    it('shows a message when the library is empty', async () => {
      commonStore.state.song_length = 0
      await this.renderComponent()

      await waitFor(() => screen.getByTestId('screen-empty-state'))
    })

    it.each<[ArtistAlbumViewMode]>([['list'], ['thumbnails']])('sets layout:%s from preferences', async (mode) => {
      preferenceStore.artists_view_mode = mode

      await this.renderComponent()

      await waitFor(() => expect(screen.getByTestId('artist-list').classList.contains(`as-${mode}`)).toBe(true))
    })

    it('switches layout', async () => {
      await this.renderComponent()

      await this.user.click(screen.getByRole('radio', { name: 'View as list' }))
      await waitFor(() => expect(screen.getByTestId('artist-list').classList.contains(`as-list`)).toBe(true))

      await this.user.click(screen.getByRole('radio', { name: 'View as thumbnails' }))
      await waitFor(() => expect(screen.getByTestId('artist-list').classList.contains(`as-thumbnails`)).toBe(true))
    })
  }

  private async renderComponent () {
    artistStore.state.artists = factory('artist', 9)

    const rendered = this.render(ArtistListScreen, {
      global: {
        stubs: {
          ArtistCard: this.stub('artist-card')
        }
      }
    })

    await this.router.activateRoute({ path: 'artists', screen: 'Artists' })
    return rendered
  }
}
