import { expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { radioStationStore } from '@/stores/radioStationStore'
import { ModalContextKey } from '@/symbols'
import { reactive, ref } from 'vue'
import Component from './EditRadioStationForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('edits a radio station without logo changing', async () => {
      const updateMock = this.mock(radioStationStore, 'update')
      const station = reactive(factory('radio-station', { is_public: false }))

      this.render(Component, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ station })],
          },
        },
      })

      await this.type(screen.getByPlaceholderText('My Favorite Radio Station'), 'Beethoven Goes Pop')
      await this.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://beet.stream/pop')
      await this.type(screen.getByPlaceholderText('A short description of the station'), 'Poppy af')

      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      expect(updateMock).toHaveBeenCalledWith(station, {
        name: 'Beethoven Goes Pop',
        url: 'https://beet.stream/pop',
        description: 'Poppy af',
        logo: null,
        is_public: false,
      })
    })

    it('removes the logo', async () => {
      const updateMock = this.mock(radioStationStore, 'update')
      const removeLogoMock = this.mock(radioStationStore, 'removeLogo')
      const station = reactive(factory('radio-station', { is_public: false }))

      this.render(Component, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ station })],
          },
        },
      })

      await this.user.click(screen.getByRole('button', { name: 'Remove' }))

      expect(removeLogoMock).toHaveBeenCalledWith(station)

      await this.type(screen.getByPlaceholderText('My Favorite Radio Station'), 'Beethoven Goes Pop')
      await this.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://beet.stream/pop')
      await this.type(screen.getByPlaceholderText('A short description of the station'), 'Poppy af')

      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      expect(updateMock).toHaveBeenCalledWith(station, {
        name: 'Beethoven Goes Pop',
        url: 'https://beet.stream/pop',
        description: 'Poppy af',
        logo: null,
        is_public: false,
      })
    })

    it('removes and replaces the logo', async () => {
      const updateMock = this.mock(radioStationStore, 'update')
      const removeLogoMock = this.mock(radioStationStore, 'removeLogo')
      const station = reactive(factory('radio-station', { is_public: false }))

      this.render(Component, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ station })],
          },
        },
      })

      await this.user.click(screen.getByRole('button', { name: 'Remove' }))
      expect(removeLogoMock).toHaveBeenCalledWith(station)

      await this.user.upload(
        screen.getByLabelText('Pick a logo (optional)'),
        new File(['bytes'], 'logo.png', { type: 'image/png' }),
      )

      await waitFor(() => screen.getByAltText('Logo'))

      await this.type(screen.getByPlaceholderText('My Favorite Radio Station'), 'Beethoven Goes Pop')
      await this.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://beet.stream/pop')
      await this.type(screen.getByPlaceholderText('A short description of the station'), 'Poppy af')

      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      expect(updateMock).toHaveBeenCalledWith(station, {
        name: 'Beethoven Goes Pop',
        url: 'https://beet.stream/pop',
        description: 'Poppy af',
        logo: 'data:image/png;base64,Ynl0ZXM=',
        is_public: false,
      })
    })
  }
}
