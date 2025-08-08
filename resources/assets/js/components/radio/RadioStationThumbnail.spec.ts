import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import Component from './RadioStationThumbnail.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('emits the clicked event', async () => {
      const { emitted } = this.renderComponent()

      await this.user.click(screen.getByRole('button'))
      expect(emitted().clicked).not.toBeNull()
    })
  }

  private renderComponent (station?: RadioStation) {
    station = station || factory('radio-station', {
      name: 'Beethoven Goes Metal',
      logo: 'https://test/beet.jpg',
    })

    const rendered = this.render(Component, {
      props: {
        station,
      },
    })

    return {
      ...rendered,
      station,
    }
  }
}
