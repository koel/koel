import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import ThemeCard from './ThemeCard.vue'

const theme: Theme = {
  id: 'sample',
  thumbnailColor: '#f00'
}

new class extends UnitTestCase {
  private renderComponent () {
    return this.render(ThemeCard, {
      props: {
        theme
      }
    })
  }

  protected test () {
    it('renders', () => {
      expect(this.renderComponent().html()).toMatchSnapshot()
    })

    it('emits an event when selected', async () => {
      const { emitted, getByTestId } = this.renderComponent()
      await fireEvent.click(getByTestId('theme-card-sample'))
      expect(emitted().selected[0]).toEqual([theme])
    })
  }
}
