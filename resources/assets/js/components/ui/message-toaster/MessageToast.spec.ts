import { expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import MessageToast from './MessageToast.vue'

new class extends UnitTestCase {
  private renderComponent () {
    return this.render(MessageToast, {
      props: {
        message: {
          id: 101,
          type: 'success',
          message: 'Everything is fine',
          timeout: 5
        }
      }
    })
  }

  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('dismisses upon click', async () => {
      const { emitted } = this.renderComponent()
      await this.user.click(screen.getByTitle('Click to dismiss'))

      expect(emitted().dismiss).toBeTruthy()
    })

    it('dismisses upon timeout', async () => {
      vi.useFakeTimers()

      const { emitted } = this.renderComponent()
      vi.advanceTimersByTime(5000)
      expect(emitted().dismiss).toBeTruthy()

      vi.useRealTimers()
    })
  }
}
