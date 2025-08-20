import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import Component from './AlbumListScreen.vue'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => this.mock(albumStore, 'paginate'))
  }

  protected test () {
    it('renders', async () => {
      await this.renderComponent()
      expect(screen.getAllByTestId('album-card')).toHaveLength(9)
    })

    it('shows a message when the library is empty', async () => {
      commonStore.state.song_length = 0
      await this.renderComponent()

      await waitFor(() => screen.getByTestId('screen-empty-state'))
    })

    it.each<[ViewMode]>([['list'], ['thumbnails']])('sets layout from preferences', async mode => {
      preferences.temporary.albums_view_mode = mode

      await this.renderComponent()

      await waitFor(() => expect(screen.getByTestId('album-grid').classList.contains(`as-${mode}`)).toBe(true))
    })

    it('switches layout', async () => {
      await this.renderComponent()
      await this.tick()

      await this.user.click(screen.getByRole('radio', { name: 'View as list' }))
      await waitFor(() => expect(screen.getByTestId('album-grid').classList.contains(`as-list`)).toBe(true))

      await this.user.click(screen.getByRole('radio', { name: 'View as thumbnails' }))
      await waitFor(() => expect(screen.getByTestId('album-grid').classList.contains(`as-thumbnails`)).toBe(true))
    })

    it('shows all or only favorites upon toggling the button', async () => {
      await this.renderComponent()
      await this.tick()

      const fetchMock = this.mock(albumStore, 'paginate')

      await this.user.click(screen.getByRole('button', { name: 'Show favorites only' }))

      await waitFor(() => expect(fetchMock).toHaveBeenNthCalledWith(1, {
        favorites_only: true,
        page: 1,
        order: 'asc',
        sort: 'name',
      }))

      await this.user.click(screen.getByRole('button', { name: 'Show all' }))

      await waitFor(() => expect(fetchMock).toHaveBeenNthCalledWith(2, {
        favorites_only: false,
        page: 1,
        order: 'asc',
        sort: 'name',
      }))
    })
  }

  private async renderComponent () {
    albumStore.state.albums = factory('album', 9)

    const rendered = this.render(Component, {
      global: {
        stubs: {
          AlbumCard: this.stub('album-card'),
        },
      },
    })

    await this.router.activateRoute({ path: 'albums', screen: 'Albums' })

    return rendered
  }
}
