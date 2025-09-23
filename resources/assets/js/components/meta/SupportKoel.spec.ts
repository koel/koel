import { describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { preferenceStore } from '@/stores/preferenceStore'
import Component from './SupportKoel.vue'

describe('supportKoel.vue', () => {
  const h = createHarness({
    beforeEach: () => vi.useFakeTimers(),
    afterEach: () => {
      vi.useRealTimers()
      preferenceStore.state.support_bar_no_bugging = false
    },
    authenticated: false, // we want to trigger preferenceStore.initialized manually
  })

  const renderComponent = async () => {
    preferenceStore.initialized.value = true
    const rendered = h.render(Component)

    vi.advanceTimersByTime(30 * 60 * 1000)
    await h.tick()

    return rendered
  }

  it('shows after a delay', async () => expect((await renderComponent()).html()).toMatchSnapshot())

  it('does not show if user so demands', async () => {
    preferenceStore.state.support_bar_no_bugging = true
    preferenceStore.initialized.value = true
    expect((await renderComponent()).queryByTestId('support-bar')).toBeNull()
  })

  it('does not show for Plus edition', async () => {
    await h.withPlusEdition(async () => {
      expect((await renderComponent()).queryByTestId('support-bar')).toBeNull()
    })
  })

  it('hides', async () => {
    await renderComponent()
    await h.user.click(screen.getByRole('button', { name: 'Hide' }))

    expect(screen.queryByTestId('support-bar')).toBeNull()
  })

  it('hides and does not bug again', async () => {
    await renderComponent()
    await h.user.click(screen.getByRole('button', { name: 'Don\'t bug me again' }))

    expect(screen.queryByTestId('support-bar')).toBeNull()
    expect(preferenceStore.state.support_bar_no_bugging).toBe(true)
  })
})
