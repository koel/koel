import Router from '@/router'
import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import { playbackService } from '@/services/QueuePlaybackService'
import { playableStore } from '@/stores/playableStore'
import Component from './GenreContextMenu.vue'
import { queueStore } from '@/stores/queueStore'

describe('genreContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = async (genre?: Genre) => {
    genre = genre || h.factory('genre', {
      name: 'Classical',
    })

    const rendered = h.beAdmin().render(Component)
    eventBus.emit('GENRE_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent, genre)
    await h.tick(2)

    return {
      ...rendered,
      genre,
    }
  }

  it('renders', async () => expect((await renderComponent()).html()).toMatchSnapshot())

  it('plays all', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 10)
    const fetchMock = h.mock(playableStore, 'fetchSongsByGenre').mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    const { genre } = await renderComponent()
    await h.user.click(screen.getByText('Play'))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(genre)
    expect(playMock).toHaveBeenCalledWith(songs)
  })

  it('shuffles all', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 10)
    const fetchMock = h.mock(playableStore, 'fetchSongsByGenre').mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    const { genre } = await renderComponent()
    await h.user.click(screen.getByText('Shuffle'))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(genre, true)
    expect(playMock).toHaveBeenCalledWith(songs)
  })

  it('adds to queue', async () => {
    const songs = h.factory('song', 10)
    const fetchMock = h.mock(playableStore, 'fetchSongsByGenre').mockResolvedValue(songs)
    const queueMock = h.mock(queueStore, 'queue')

    const { genre } = await renderComponent()
    await h.user.click(screen.getByText('Add to Queue'))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(genre)
    expect(queueMock).toHaveBeenCalledWith(songs)
  })

  it('goes to genre', async () => {
    const mock = h.mock(Router, 'go')
    const { genre } = await renderComponent()

    await h.user.click(screen.getByText('View Genre'))

    expect(mock).toHaveBeenCalledWith(`/#/genres/${genre.id}`)
  })
})
