import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/RadioPlaybackService'
import Component from './RadioStationRow.vue'

describe('radioStationRow.vue', () => {
  const h = createHarness()

  const renderComponent = (overrides: Partial<RadioStation> = {}) => {
    const station = h.factory('radio-station').make({
      id: 'wcpe',
      name: 'WCPE',
      description: 'The Classical Station',
      logo: 'https://example.com/wcpe.png',
      favorite: false,
      ...overrides,
    })

    return { station, ...h.render(Component, { props: { station } }) }
  }

  it('emits toggle-favorite when the favorite button is clicked', async () => {
    const { station, emitted } = renderComponent({ favorite: true })

    await h.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))

    expect(emitted('toggle-favorite')?.[0]).toEqual([station])
  })

  it('toggles playback on double-click', async () => {
    h.createAudioPlayer()
    const playMock = h.mock(playbackService, 'play')
    const { station } = renderComponent()

    await h.user.dblClick(screen.getByTestId('radio-station-row'))

    expect(playMock).toHaveBeenCalledWith(station)
  })
})
