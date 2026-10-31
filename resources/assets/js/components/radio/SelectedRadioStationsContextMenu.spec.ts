import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { radioStationStore } from '@/stores/radioStationStore'
import Component from './SelectedRadioStationsContextMenu.vue'

describe('selectedRadioStationsContextMenu.vue', () => {
  const h = createHarness()

  it('favorites when any station is not a favorite', async () => {
    const favoriteMock = h.mock(radioStationStore, 'favorite')
    const stations = h.factory('radio-station').make({ favorite: true }, 3)
    stations[1].favorite = false
    h.render(Component, { props: { stations } })

    await h.user.click(screen.getByText('Favorite'))

    expect(favoriteMock).toHaveBeenCalledWith(stations)
  })

  it('undoes favorite when all stations are favorites', async () => {
    const undoFavoriteMock = h.mock(radioStationStore, 'undoFavorite')
    const stations = h.factory('radio-station').make({ favorite: true }, 3)
    h.render(Component, { props: { stations } })

    await h.user.click(screen.getByText('Undo Favorite'))

    expect(undoFavoriteMock).toHaveBeenCalledWith(stations)
  })
})
