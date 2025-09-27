import type { Mock } from 'vitest'
import { describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/RadioPlaybackService'
import { radioStationStore } from '@/stores/radioStationStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import RadioStationContextMenu from '@/components/radio/RadioStationContextMenu.vue'
import Component from './RadioStationCard.vue'

describe('radioStationCard.vue', () => {
  const h = createHarness()

  const createRadioStation = (overrides: Partial<RadioStation> = {}): RadioStation => {
    return h.factory('radio-station', {
      id: '----',
      name: 'Beethoven Goes Metal',
      url: 'https://beet.stream/metal',
      logo: 'https://example.com/logo.jpg',
      description: 'Heavy af',
      is_public: false,
      favorite: false,
      ...overrides,
    })
  }

  const renderComponent = (station?: RadioStation) => {
    station = station || createRadioStation()

    const render = h.render(Component, {
      props: {
        station: station || createRadioStation(),
      },
      global: {
        stubs: {
          FavoriteButton: h.stub('favorite-button', true),
        },
      },
    })

    return {
      ...render,
      station,
    }
  }

  it('renders with stopped state', () => expect(renderComponent().html()).toMatchSnapshot())

  it('renders with playing state', () => {
    const station = createRadioStation({ playback_state: 'Playing' })
    expect(renderComponent(station).html()).toMatchSnapshot()
  })

  it('plays', async () => {
    h.createAudioPlayer()

    const playMock = h.mock(playbackService, 'play')
    const { station } = renderComponent()

    await h.user.click(screen.getByTitle('Play/pause Beethoven Goes Metal'))

    expect(playMock).toHaveBeenCalledWith(station)
  })

  it('requests context menu', async () => {
    vi.mock('@/composables/useContextMenu')
    const { openContextMenu } = useContextMenu()
    const { station } = renderComponent()
    await h.trigger(screen.getByTestId('radio-station-card'), 'contextMenu')

    await assertOpenContextMenu(openContextMenu as Mock, RadioStationContextMenu, { station })
  })

  it('if favorite, has a Favorite icon button that undoes favorite state', async () => {
    const album = createRadioStation({ favorite: true })
    const toggleMock = h.mock(radioStationStore, 'toggleFavorite')
    renderComponent(album)

    await fireEvent(screen.getByTestId('favorite-button'), new CustomEvent('toggle'))

    expect(toggleMock).toHaveBeenCalledWith(album)
  })

  it('if not favorite, does not have a Favorite icon button', async () => {
    renderComponent()
    expect(screen.queryByRole('button', { name: 'Undo Favorite' })).toBeNull()
  })
})
