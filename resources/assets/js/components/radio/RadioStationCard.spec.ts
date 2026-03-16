import type { Mock } from 'vite-plus/test'
import { describe, expect, it, vi } from 'vite-plus/test'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/RadioPlaybackService'
import { radioStationStore } from '@/stores/radioStationStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import RadioStationContextMenu from '@/components/radio/RadioStationContextMenu.vue'
import Component from './RadioStationCard.vue'

vi.mock('@/composables/useContextMenu')

describe('radioStationCard', () => {
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
        station,
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

  it('renders station name', () => {
    renderComponent()
    screen.getByText('Beethoven Goes Metal')
  })

  it('renders station description', () => {
    renderComponent()
    screen.getByText('Heavy af')
  })

  it('renders thumbnail with play/pause button', () => {
    renderComponent()
    screen.getByTitle('Play/pause Beethoven Goes Metal')
  })

  it('plays on thumbnail click', async () => {
    h.createAudioPlayer()
    const playMock = h.mock(playbackService, 'play')
    const { station } = renderComponent()

    await h.user.click(screen.getByTitle('Play/pause Beethoven Goes Metal'))

    expect(playMock).toHaveBeenCalledWith(station)
  })

  it('requests context menu', async () => {
    const { openContextMenu } = useContextMenu()
    const { station } = renderComponent()
    await h.trigger(screen.getByTestId('artist-album-card'), 'contextMenu')

    await assertOpenContextMenu(openContextMenu as Mock, RadioStationContextMenu, { station })
  })

  it('shows favorite button when favorited', async () => {
    const station = createRadioStation({ favorite: true })
    const toggleMock = h.mock(radioStationStore, 'toggleFavorite')
    renderComponent(station)

    await fireEvent(screen.getByTestId('favorite-button'), new CustomEvent('toggle'))

    expect(toggleMock).toHaveBeenCalledWith(station)
  })

  it('does not show favorite button when not favorited', () => {
    renderComponent()
    expect(screen.queryByTestId('favorite-button')).toBeNull()
  })
})
