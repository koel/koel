import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { radioStationStore } from '@/stores/radioStationStore'
import Component from './AddRadioStationForm.vue'

describe('addRadioStationForm.vue', () => {
  const h = createHarness()

  it('adds a radio station without a logo', async () => {
    const storeMock = h.mock(radioStationStore, 'store').mockResolvedValue(h.factory('radio-station'))

    h.render(Component)
    await h.type(screen.getByPlaceholderText('My Favorite Radio Station'), 'Beethoven Goes Metal')
    await h.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://beet.stream/metal')
    await h.type(screen.getByPlaceholderText('A short description of the station'), 'Heavy af')

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(storeMock).toHaveBeenCalledWith({
      name: 'Beethoven Goes Metal',
      url: 'https://beet.stream/metal',
      description: 'Heavy af',
      logo: null,
      is_public: false,
    })
  })

  it('adds a radio station with a logo', async () => {
    const storeMock = h.mock(radioStationStore, 'store').mockResolvedValue(h.factory('radio-station'))

    h.render(Component)
    await h.type(screen.getByPlaceholderText('My Favorite Radio Station'), 'Beethoven Goes Metal')
    await h.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://beet.stream/metal')
    await h.type(screen.getByPlaceholderText('A short description of the station'), 'Heavy af')

    await h.user.upload(
      screen.getByLabelText('Pick a logo (optional)'),
      new File(['bytes'], 'logo.png', { type: 'image/png' }),
    )

    await waitFor(() => screen.getByAltText('Logo'))

    await h.user.click(screen.getByLabelText('Make this station public'))
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(storeMock).toHaveBeenCalledWith({
      name: 'Beethoven Goes Metal',
      url: 'https://beet.stream/metal',
      description: 'Heavy af',
      logo: 'data:image/png;base64,Ynl0ZXM=',
      is_public: true,
    })
  })
})
