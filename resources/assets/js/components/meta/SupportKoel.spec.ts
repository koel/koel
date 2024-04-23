import { expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import { preferenceStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SupportKoel from './SupportKoel.vue'
import { http } from '@/services'

new class extends UnitTestCase {
  protected beforeEach () {
    // Prevent actual HTTP requests from being made
    this.setReadOnlyProperty(http, 'silently', {
      patch: vi.fn()
    })

    super.beforeEach(() => vi.useFakeTimers())
  }

  protected afterEach () {
    super.afterEach(() => {
      vi.useRealTimers()
      preferenceStore.state.support_bar_no_bugging = false
    })
  }

  protected test () {
    it('shows after a delay', async () => expect((await this.renderComponent()).html()).toMatchSnapshot())

    it('does not show if user so demands', async () => {
      preferenceStore.state.support_bar_no_bugging = true
      preferenceStore.initialized.value = true
      expect((await this.renderComponent()).queryByTestId('support-bar')).toBeNull()
    })

    it('does not show for Plus edition', async () => {
      this.enablePlusEdition()
      expect((await this.renderComponent()).queryByTestId('support-bar')).toBeNull()
    })

    it('hides', async () => {
      await this.renderComponent()
      await this.user.click(screen.getByRole('button', { name: 'Hide' }))

      expect(screen.queryByTestId('support-bar')).toBeNull()
    })

    it('hides and does not bug again', async () => {
      await this.renderComponent()
      await this.user.click(screen.getByRole('button', { name: 'Don\'t bug me again' }))

      expect(await screen.queryByTestId('support-bar')).toBeNull()
      expect(preferenceStore.state.support_bar_no_bugging).toBe(true)
    })
  }

  private async renderComponent () {
    preferenceStore.initialized.value = true
    const rendered = this.render(SupportKoel)

    vi.advanceTimersByTime(30 * 60 * 1000)
    await this.tick()

    return rendered
  }
}
