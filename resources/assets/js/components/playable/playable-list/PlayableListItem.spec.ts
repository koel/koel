import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { playableStore } from '@/stores/playableStore'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PlayableListItem.vue'

describe('playableListItem.vue', () => {
  const h = createHarness()

  const renderComponent = (playable?: Playable, showDisc = false) => {
    playable = playable ?? h.factory('song', { favorite: false })

    const row = {
      playable,
      selected: false,
    }

    const rendered = h.render(Component, {
      props: {
        item: row,
        showDisc,
      },
    })

    return {
      ...rendered,
      row,
    }
  }

  it('renders', async () => {
    const song = h.factory('song', {
      title: 'Test Song',
      album_name: 'Test Album',
      artist_name: 'Test Artist',
      length: 1000,
      playback_state: 'Playing',
      track: 12,
      album_cover: 'https://example.com/cover.jpg',
      favorite: true,
    })

    expect(renderComponent(song).html()).toMatchSnapshot()
  })

  it('emits play event on double click', async () => {
    const { emitted } = renderComponent()
    await h.user.dblClick(screen.getByTestId('song-item'))
    expect(emitted().play).toBeTruthy()
  })

  it('renders disc info when showDisc is true', async () => {
    const song = h.factory('song', {
      disc: 2,
      title: 'Test Song',
    })

    const showDisc = true
    const { getByText } = renderComponent(song, showDisc)
    expect(getByText('Disc 2')).toBeTruthy()
  })

  it('toggles favorite state when the Favorite button is clicked', async () => {
    const toggleFavoriteMock = h.mock(playableStore, 'toggleFavorite')
    const { row } = renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Favorite' }))

    expect(toggleFavoriteMock).toHaveBeenCalledWith(row.playable)
  })
})
