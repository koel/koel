import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { alerts, arrayify } from '@/utils'
import SongEditForm from './SongEditForm.vue'
import { EditSongFormInitialTabKey, SongsKey } from '@/symbols'
import { ref } from 'vue'
import { fireEvent } from '@testing-library/vue'
import { songStore } from '@/stores'

let songs: Song[]

new class extends UnitTestCase {
  private async renderComponent (_songs: Song | Song[], initialTab: EditSongFormTabName = 'details') {
    songs = arrayify(_songs)

    const rendered = this.render(SongEditForm, {
      global: {
        provide: {
          [SongsKey]: [ref(songs)],
          [EditSongFormInitialTabKey]: [ref(initialTab)]
        }
      }
    })

    await this.tick()

    return rendered
  }

  protected test () {
    it('edits a single song', async () => {
      const updateMock = this.mock(songStore, 'update')
      const alertMock = this.mock(alerts, 'success')

      const { html, getByTestId, getByRole } = await this.renderComponent(factory<Song>('song', {
        title: 'Rocket to Heaven',
        artist_name: 'Led Zeppelin',
        album_name: 'IV',
        album_cover: 'https://example.co/album.jpg'
      }))

      expect(html()).toMatchSnapshot()

      await fireEvent.update(getByTestId('title-input'), 'Highway to Hell')
      await fireEvent.update(getByTestId('artist-input'), 'AC/DC')
      await fireEvent.update(getByTestId('albumArtist-input'), 'AC/DC')
      await fireEvent.update(getByTestId('album-input'), 'Back in Black')
      await fireEvent.update(getByTestId('disc-input'), '1')
      await fireEvent.update(getByTestId('track-input'), '10')
      await fireEvent.update(getByTestId('lyrics-input'), 'I\'m gonna make him an offer he can\'t refuse')

      await fireEvent.click(getByRole('button', { name: 'Update' }))

      expect(updateMock).toHaveBeenCalledWith(songs, {
        title: 'Highway to Hell',
        album_name: 'Back in Black',
        artist_name: 'AC/DC',
        album_artist_name: 'AC/DC',
        lyrics: 'I\'m gonna make him an offer he can\'t refuse',
        track: 10,
        disc: 1
      })

      expect(alertMock).toHaveBeenCalledWith('Updated 1 song.')
    })

    it('edits multiple songs', async () => {
      const updateMock = this.mock(songStore, 'update')
      const alertMock = this.mock(alerts, 'success')

      const { html, getByTestId, getByRole, queryByTestId } = await this.renderComponent(factory<Song[]>('song', 3))

      expect(html()).toMatchSnapshot()
      expect(queryByTestId('title-input')).toBeNull()
      expect(queryByTestId('lyrics-input')).toBeNull()

      await fireEvent.update(getByTestId('artist-input'), 'AC/DC')
      await fireEvent.update(getByTestId('albumArtist-input'), 'AC/DC')
      await fireEvent.update(getByTestId('album-input'), 'Back in Black')
      await fireEvent.update(getByTestId('disc-input'), '1')
      await fireEvent.update(getByTestId('track-input'), '10')

      await fireEvent.click(getByRole('button', { name: 'Update' }))

      expect(updateMock).toHaveBeenCalledWith(songs, {
        album_name: 'Back in Black',
        artist_name: 'AC/DC',
        album_artist_name: 'AC/DC',
        track: 10,
        disc: 1
      })

      expect(alertMock).toHaveBeenCalledWith('Updated 3 songs.')
    })

    it('displays artist name if all songs have the same artist', async () => {
      const { getByTestId } = await this.renderComponent(factory<Song[]>('song', {
        artist_id: 1000,
        artist_name: 'Led Zeppelin',
        album_id: 1001,
        album_name: 'IV'
      }, 4))

      expect(getByTestId('displayed-artist-name').textContent).toBe('Led Zeppelin')
      expect(getByTestId('displayed-album-name').textContent).toBe('IV')
    })
  }
}
