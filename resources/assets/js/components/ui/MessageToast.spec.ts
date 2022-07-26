import { expect, it, vi } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import MessageToast from './MessageToast.vue'
import { fireEvent } from '@testing-library/vue'

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
      const { emitted, getByTitle } = this.renderComponent()
      await fireEvent.click(getByTitle('Click to dismiss'))

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
