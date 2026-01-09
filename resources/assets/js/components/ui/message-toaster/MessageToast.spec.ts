import { describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './MessageToast.vue'

describe('messageToast.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    return h.render(Component, {
      props: {
        message: {
          id: '101',
          type: 'success',
          content: 'Everything is fine',
          timeout: 5,
        },
      },
    })
  }

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())

  it('dismisses upon click', async () => {
    const { emitted } = renderComponent()
    await h.user.click(screen.getByTitle('Click to dismiss'))

    expect(emitted().dismiss).toBeTruthy()
  })

  it('dismisses upon timeout', async () => {
    // NOTE: This test has a known issue with vue-i18n and fake timers.
    // vue-i18n (specifically @intlify) uses performance.now() internally which
    // conflicts with Vitest's fake timers, causing invalid timestamp errors.
    // 
    // Workaround: Use real timers with a short timeout and verify the event is emitted.
    // This is slower but works around the vue-i18n compatibility issue.
    const { emitted, unmount } = renderComponent()
    
    // Wait for the timeout to trigger (5 seconds + small buffer)
    await new Promise(resolve => setTimeout(resolve, 5100))
    
    expect(emitted().dismiss).toBeTruthy()
    unmount()
  }, 10000) // Increase test timeout to accommodate real timer
})
