import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'

vi.mock('@/stores/commonStore', () => ({
  commonStore: {
    state: {
      koel_plus: {
        active: true,
        short_key: 'ABC-123',
        customer_name: 'John Doe',
        customer_email: 'john@example.com',
        product_id: 'prod_xyz',
      },
    },
  },
}))

import { useKoelPlus } from './useKoelPlus'

describe('useKoelPlus', () => {
  const h = createHarness()

  it('builds checkout URL from product id', () => {
    const { checkoutUrl } = useKoelPlus()
    expect(checkoutUrl.value).toContain('prod_xyz')
    expect(checkoutUrl.value).toContain('store.koel.dev/checkout')
  })

  it('requests Lemon Squeezy again until it initializes', async () => {
    const lemonSqueezyScriptSelector = 'script[src="https://app.lemonsqueezy.com/js/lemon.js"]'
    const lemonSqueezy = { Url: { Open: vi.fn() } }
    const { loadLemonSqueezy } = useKoelPlus()

    const dispatchScriptEvent = (eventType: 'load' | 'error') =>
      document.head.querySelector(lemonSqueezyScriptSelector)!.dispatchEvent(new Event(eventType))

    const blockedAttempt = loadLemonSqueezy()
    dispatchScriptEvent('error')

    await expect(blockedAttempt).rejects.toThrow('Failed to load Lemon Squeezy')
    expect(document.head.querySelector(lemonSqueezyScriptSelector)).toBeNull()

    const uninitializedAttempt = loadLemonSqueezy()
    dispatchScriptEvent('load')

    await expect(uninitializedAttempt).rejects.toThrow('Failed to load Lemon Squeezy')
    expect(document.head.querySelector(lemonSqueezyScriptSelector)).toBeNull()

    h.mock(window, 'createLemonSqueezy', () => {
      window.LemonSqueezy = lemonSqueezy
    })

    const successfulAttempt = loadLemonSqueezy()
    dispatchScriptEvent('load')

    await expect(successfulAttempt).resolves.toBe(lemonSqueezy)

    loadLemonSqueezy()
    expect(document.head.querySelectorAll(lemonSqueezyScriptSelector)).toHaveLength(1)
  })
})
