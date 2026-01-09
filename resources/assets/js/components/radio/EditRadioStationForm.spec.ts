import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { radioStationStore } from '@/stores/radioStationStore'
import type { Reactive } from 'vue'
import { reactive } from 'vue'
import Component from './EditRadioStationForm.vue'

describe('editRadioStationForm.vue', () => {
  const h = createHarness()

  const renderComponent = (station?: RadioStation | Reactive<RadioStation>) => {
    station = station ?? h.factory('radio-station')

    const rendered = h.render(Component, {
      props: {
        station,
      },
    })

    return {
      ...rendered,
      station,
    }
  }

  it('edits a radio station without logo changing', async () => {
    const updateMock = h.mock(radioStationStore, 'update')
    const { station } = renderComponent(reactive(h.factory('radio-station', { is_public: false })))

    await h.type(screen.getByPlaceholderText(/My Favorite Radio Station/i), 'Beethoven Goes Pop')
    await h.type(screen.getByPlaceholderText(/https:\/\/radio\.example\.com\/stream/i), 'https://beet.stream/pop')
    await h.type(screen.getByPlaceholderText(/A short description of the station/i), 'Poppy af')

    await h.user.click(screen.getByRole('button', { name: /Save/i }))

    expect(updateMock).toHaveBeenCalledWith(station, {
      name: 'Beethoven Goes Pop',
      url: 'https://beet.stream/pop',
      description: 'Poppy af',
      is_public: false,
    })
  })

  it('edits a radio station, removing the logo', async () => {
    const updateMock = h.mock(radioStationStore, 'update')
    const { station } = renderComponent(reactive(h.factory('radio-station', { is_public: false })))

    await h.user.click(screen.getByRole('button', { name: /Remove/i }))

    await h.type(screen.getByPlaceholderText(/My Favorite Radio Station/i), 'Beethoven Goes Pop')
    await h.type(screen.getByPlaceholderText(/https:\/\/radio\.example\.com\/stream/i), 'https://beet.stream/pop')
    await h.type(screen.getByPlaceholderText(/A short description of the station/i), 'Poppy af')

    await h.user.click(screen.getByRole('button', { name: /Save/i }))

    expect(updateMock).toHaveBeenCalledWith(station, {
      name: 'Beethoven Goes Pop',
      url: 'https://beet.stream/pop',
      description: 'Poppy af',
      logo: '',
      is_public: false,
    })
  })

  it('edits a radio station, replacing the logo', async () => {
    const updateMock = h.mock(radioStationStore, 'update')
    const { station } = renderComponent(reactive(h.factory('radio-station', { is_public: false })))

    await h.user.click(screen.getByRole('button', { name: /Remove/i }))

    await h.user.upload(
      screen.getByLabelText(/Pick a logo/i),
      new File(['bytes'], 'logo.png', { type: 'image/png' }),
    )

    await waitFor(() => screen.getByRole('img'))

    await h.type(screen.getByPlaceholderText(/My Favorite Radio Station/i), 'Beethoven Goes Pop')
    await h.type(screen.getByPlaceholderText(/https:\/\/radio\.example\.com\/stream/i), 'https://beet.stream/pop')
    await h.type(screen.getByPlaceholderText(/A short description of the station/i), 'Poppy af')

    await h.user.click(screen.getByRole('button', { name: /Save/i }))

    expect(updateMock).toHaveBeenCalledWith(station, {
      name: 'Beethoven Goes Pop',
      url: 'https://beet.stream/pop',
      description: 'Poppy af',
      logo: 'data:image/png;base64,Ynl0ZXM=',
      is_public: false,
    })
  })
})
