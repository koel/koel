import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { radioStationStore } from '@/stores/radioStationStore'
import { ModalContextKey } from '@/symbols'
import { reactive, ref } from 'vue'
import Component from './EditRadioStationForm.vue'

describe('editRadioStationForm.vue', () => {
  const h = createHarness()

  it('edits a radio station without logo changing', async () => {
    const updateMock = h.mock(radioStationStore, 'update')
    const station = reactive(h.factory('radio-station', { is_public: false }))

    h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: ref({ station }),
        },
      },
    })

    await h.type(screen.getByPlaceholderText('My Favorite Radio Station'), 'Beethoven Goes Pop')
    await h.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://beet.stream/pop')
    await h.type(screen.getByPlaceholderText('A short description of the station'), 'Poppy af')

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(station, {
      name: 'Beethoven Goes Pop',
      url: 'https://beet.stream/pop',
      description: 'Poppy af',
      logo: null,
      is_public: false,
    })
  })

  it('removes the logo', async () => {
    const updateMock = h.mock(radioStationStore, 'update')
    const removeLogoMock = h.mock(radioStationStore, 'removeLogo')
    const station = reactive(h.factory('radio-station', { is_public: false }))

    h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: ref({ station }),
        },
      },
    })

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))

    expect(removeLogoMock).toHaveBeenCalledWith(station)

    await h.type(screen.getByPlaceholderText('My Favorite Radio Station'), 'Beethoven Goes Pop')
    await h.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://beet.stream/pop')
    await h.type(screen.getByPlaceholderText('A short description of the station'), 'Poppy af')

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(station, {
      name: 'Beethoven Goes Pop',
      url: 'https://beet.stream/pop',
      description: 'Poppy af',
      logo: null,
      is_public: false,
    })
  })

  it('removes and replaces the logo', async () => {
    const updateMock = h.mock(radioStationStore, 'update')
    const removeLogoMock = h.mock(radioStationStore, 'removeLogo')
    const station = reactive(h.factory('radio-station', { is_public: false }))

    h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: ref({ station }),
        },
      },
    })

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))
    expect(removeLogoMock).toHaveBeenCalledWith(station)

    await h.user.upload(
      screen.getByLabelText('Pick a logo (optional)'),
      new File(['bytes'], 'logo.png', { type: 'image/png' }),
    )

    await waitFor(() => screen.getByAltText('Logo'))

    await h.type(screen.getByPlaceholderText('My Favorite Radio Station'), 'Beethoven Goes Pop')
    await h.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://beet.stream/pop')
    await h.type(screen.getByPlaceholderText('A short description of the station'), 'Poppy af')

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(station, {
      name: 'Beethoven Goes Pop',
      url: 'https://beet.stream/pop',
      description: 'Poppy af',
      logo: 'data:image/png;base64,Ynl0ZXM=',
      is_public: false,
    })
  })
})
