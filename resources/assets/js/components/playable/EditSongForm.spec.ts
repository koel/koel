import { screen } from '@testing-library/vue'
import { ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { arrayify } from '@/utils/helpers'
import { eventBus } from '@/utils/eventBus'
import { ModalContextKey } from '@/symbols'
import type { SongUpdateResult } from '@/stores/playableStore'
import { playableStore as songStore } from '@/stores/playableStore'
import { MessageToasterStub } from '@/__tests__/stubs'
import Component from './EditSongForm.vue'

describe('editSongForm.vue', () => {
  const h = createHarness()

  const renderComponent = async (songs: MaybeArray<Song>, initialTab: EditSongFormTabName = 'details') => {
    songs = arrayify(songs)

    const rendered = h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: ref({
            songs,
            initialTab,
          }),
        },
      },
    })

    await h.tick()

    return {
      ...rendered,
      songs,
    }
  }

  it('edits a single song', async () => {
    const result: SongUpdateResult = {
      songs: [],
      albums: [],
      artists: [],
      removed: {
        albums: [],
        artists: [],
      },
    }

    const updateMock = h.mock(songStore, 'updateSongs').mockResolvedValue(result)
    const emitMock = h.mock(eventBus, 'emit')
    const alertMock = h.mock(MessageToasterStub.value, 'success')

    const { songs, html } = await renderComponent(h.factory('song', {
      title: 'Rocket to Heaven',
      artist_name: 'Led Zeppelin',
      album_name: 'IV',
      album_cover: 'http://test/album.jpg',
      genre: 'Rock',
    }))

    expect(html()).toMatchSnapshot()

    await h.type(screen.getByTestId('title-input'), 'Highway to Hell')
    await h.type(screen.getByTestId('artist-input'), 'AC/DC')
    await h.type(screen.getByTestId('albumArtist-input'), 'AC/DC')
    await h.type(screen.getByTestId('album-input'), 'Back in Black')
    await h.type(screen.getByTestId('disc-input'), '1')
    await h.type(screen.getByTestId('track-input'), '10')
    await h.type(screen.getByTestId('genre-input'), 'Rock & Roll')
    await h.type(screen.getByTestId('year-input'), '1971')
    await h.type(screen.getByTestId('lyrics-input'), 'I\'m gonna make him an offer he can\'t refuse')

    await h.user.click(screen.getByRole('button', { name: 'Update' }))

    expect(updateMock).toHaveBeenCalledWith(songs, {
      title: 'Highway to Hell',
      album_name: 'Back in Black',
      artist_name: 'AC/DC',
      album_artist_name: 'AC/DC',
      lyrics: 'I\'m gonna make him an offer he can\'t refuse',
      track: 10,
      disc: 1,
      genre: 'Rock & Roll',
      year: 1971,
    })

    expect(alertMock).toHaveBeenCalledWith('Updated 1 song.')
    expect(emitMock).toHaveBeenCalledWith('SONGS_UPDATED', result)
  })

  it('edits multiple songs', async () => {
    const result: SongUpdateResult = {
      songs: [],
      albums: [],
      artists: [],
      removed: {
        albums: [],
        artists: [],
      },
    }

    const updateMock = h.mock(songStore, 'updateSongs').mockResolvedValue(result)
    const emitMock = h.mock(eventBus, 'emit')
    const alertMock = h.mock(MessageToasterStub.value, 'success')

    const { songs, html } = await renderComponent(h.factory('song', 3))

    expect(html()).toMatchSnapshot()
    expect(screen.queryByTestId('title-input')).toBeNull()
    expect(screen.queryByTestId('lyrics-input')).toBeNull()

    await h.type(screen.getByTestId('artist-input'), 'AC/DC')
    await h.type(screen.getByTestId('albumArtist-input'), 'AC/DC')
    await h.type(screen.getByTestId('album-input'), 'Back in Black')
    await h.type(screen.getByTestId('disc-input'), '1')
    await h.type(screen.getByTestId('track-input'), '10')
    await h.type(screen.getByTestId('year-input'), '1990')
    await h.type(screen.getByTestId('genre-input'), 'Pop')

    await h.user.click(screen.getByRole('button', { name: 'Update' }))

    expect(updateMock).toHaveBeenCalledWith(songs, {
      album_name: 'Back in Black',
      artist_name: 'AC/DC',
      album_artist_name: 'AC/DC',
      track: 10,
      disc: 1,
      genre: 'Pop',
      year: 1990,
    })

    expect(alertMock).toHaveBeenCalledWith('Updated 3 songs.')
    expect(emitMock).toHaveBeenCalledWith('SONGS_UPDATED', result)
  })

  it('displays artist name if all songs have the same artist', async () => {
    await renderComponent(h.factory('song', 4, {
      artist_id: 'led-zeppelin',
      artist_name: 'Led Zeppelin',
      album_id: 'iv',
      album_name: 'IV',
    }))

    expect(screen.getByTestId('displayed-artist-name').textContent).toBe('Led Zeppelin')
    expect(screen.getByTestId('displayed-album-name').textContent).toBe('IV')
  })
})
