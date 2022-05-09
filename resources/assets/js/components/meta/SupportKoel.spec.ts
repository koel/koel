import { expect, it, vi } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import { eventBus } from '@/utils'
import { preferenceStore } from '@/stores'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import SupportKoel from './SupportKoel.vue'

new class extends ComponentTestCase {
  protected beforeEach () {
    super.beforeEach(() => vi.useFakeTimers());
  }

  protected afterEach () {
    super.afterEach(() => {
      vi.useRealTimers()
      preferenceStore.state.supportBarNoBugging = false
    })
  }

  private async renderComponent () {
    const result = this.render(SupportKoel)
    eventBus.emit('KOEL_READY')

    vi.advanceTimersByTime(30 * 60 * 1000)
    await this.tick()

    return result
  }

  protected test () {
    it('shows after a delay', async () => {
      expect((await this.renderComponent()).html()).toMatchSnapshot()
    })

    it('does not show if user so demands', async () => {
      preferenceStore.state.supportBarNoBugging = true
      expect((await this.renderComponent()).queryByTestId('support-bar')).toBeNull()
    })

    it('hides', async () => {
      const { getByTestId, queryByTestId } = await this.renderComponent()

      await fireEvent.click(getByTestId('hide-support-koel'))

      expect(await queryByTestId('support-bar')).toBeNull()
    })

    it('hides and does not bug again', async () => {
      const { getByTestId, queryByTestId } = await this.renderComponent()

      await fireEvent.click(getByTestId('stop-support-koel-bugging'))

      expect(await queryByTestId('btn-stop-support-koel-bugging')).toBeNull()
      expect(preferenceStore.state.supportBarNoBugging).toBe(true)
    })
  }
}
