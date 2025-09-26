import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/QueuePlaybackService'
import { playableStore } from '@/stores/playableStore'
import { queueStore } from '@/stores/queueStore'
import Router from '@/router'
import Component from './GenreContextMenu.vue'

describe('genreContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = async (genre?: Genre) => {
    genre = genre || h.factory('genre', {
      name: 'Classical',
    })

    const rendered = h.render(Component, {
      props: {
        genre,
      },
    })

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
    const goMock = h.mock(Router, 'go')

    const { genre } = await renderComponent()
    await h.user.click(screen.getByText('Play'))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(genre)
    expect(playMock).toHaveBeenCalledWith(songs)
    expect(goMock).toHaveBeenCalledWith('queue')
  })

  it('shuffles all', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 10)
    const fetchMock = h.mock(playableStore, 'fetchSongsByGenre').mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')
    const goMock = h.mock(Router, 'go')

    const { genre } = await renderComponent()
    await h.user.click(screen.getByText('Shuffle'))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(genre, true)
    expect(playMock).toHaveBeenCalledWith(songs)
    expect(goMock).toHaveBeenCalledWith('queue')
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
})
