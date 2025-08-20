import { expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { radioStationStore } from '@/stores/radioStationStore'
import Component from './AddRadioStationForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('adds a radio station without a logo', async () => {
      const storeMock = this.mock(radioStationStore, 'store')

      this.render(Component)
      await this.type(screen.getByPlaceholderText('My Favorite Radio Station'), 'Beethoven Goes Metal')
      await this.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://beet.stream/metal')
      await this.type(screen.getByPlaceholderText('A short description of the station'), 'Heavy af')

      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      expect(storeMock).toHaveBeenCalledWith({
        name: 'Beethoven Goes Metal',
        url: 'https://beet.stream/metal',
        description: 'Heavy af',
        logo: null,
        is_public: false,
      })
    })

    it('adds a radio station with a logo', async () => {
      const storeMock = this.mock(radioStationStore, 'store')
      this.render(Component)

      await this.type(screen.getByPlaceholderText('My Favorite Radio Station'), 'Beethoven Goes Metal')
      await this.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://beet.stream/metal')
      await this.type(screen.getByPlaceholderText('A short description of the station'), 'Heavy af')

      await this.user.upload(
        screen.getByLabelText('Pick a logo (optional)'),
        new File(['bytes'], 'logo.png', { type: 'image/png' }),
      )

      await waitFor(() => screen.getByAltText('Logo'))

      await this.user.click(screen.getByLabelText('Make this station public'))
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      expect(storeMock).toHaveBeenCalledWith({
        name: 'Beethoven Goes Metal',
        url: 'https://beet.stream/metal',
        description: 'Heavy af',
        logo: 'data:image/png;base64,Ynl0ZXM=',
        is_public: true,
      })
    })
  }
}
