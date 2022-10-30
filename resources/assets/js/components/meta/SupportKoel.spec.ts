import { expect, it, vi } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import { preferenceStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SupportKoel from './SupportKoel.vue'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => vi.useFakeTimers())
  }

  protected afterEach () {
    super.afterEach(() => {
      vi.useRealTimers()
      preferenceStore.state.supportBarNoBugging = false
    })
  }

  private async renderComponent () {
    preferenceStore.initialized.value = true
    const rendered = this.render(SupportKoel)

    vi.advanceTimersByTime(30 * 60 * 1000)
    await this.tick()

    return rendered
  }

  protected test () {
    it('shows after a delay', async () => {
      expect((await this.renderComponent()).html()).toMatchSnapshot()
    })

    it('does not show if user so demands', async () => {
      preferenceStore.state.supportBarNoBugging = true
      preferenceStore.initialized.value = true
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
