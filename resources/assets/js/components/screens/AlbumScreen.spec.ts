import { waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import AlbumScreen from './AlbumScreen.vue'
import { commonStore, songStore } from '@/stores'

new class extends UnitTestCase {
  protected async renderComponent () {
    commonStore.state.use_last_fm = true

    const album = factory<Album>('album', {
      name: 'Led Zeppelin IV',
      artist_name: 'Led Zeppelin',
      song_count: 10,
      length: 1_603
    })

    const songs = factory<Song[]>('song', 13)
    const fetchSongsMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(songs)

    const rendered = this.render(AlbumScreen, {
      props: {
        album
      }
    })

    await waitFor(() => expect(fetchSongsMock).toHaveBeenCalledWith(album))

    return rendered
  }

  protected test () {
    it('renders', async () => {
      const { getAllByTestId, getByText } = await this.renderComponent()
      getByText(/10 songs.+26:43$/)
    })
  }
}
