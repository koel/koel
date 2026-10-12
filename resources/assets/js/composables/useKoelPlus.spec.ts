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
  createHarness()

  it('builds checkout URL from product id', () => {
    const { checkoutUrl } = useKoelPlus()
    expect(checkoutUrl.value).toContain('prod_xyz')
    expect(checkoutUrl.value).toContain('store.koel.dev/checkout')
  })

  it('requests Lemon Squeezy again after it fails to load', async () => {
    const lemonSqueezyScriptSelector = 'script[src="https://app.lemonsqueezy.com/js/lemon.js"]'
    const { loadLemonSqueezy } = useKoelPlus()

    const failedAttempt = loadLemonSqueezy()
    document.head.querySelector(lemonSqueezyScriptSelector)!.dispatchEvent(new Event('error'))

    await expect(failedAttempt).rejects.toThrow('Failed to load Lemon Squeezy')
    expect(document.head.querySelector(lemonSqueezyScriptSelector)).toBeNull()

    const retry = loadLemonSqueezy()
    document.head.querySelector(lemonSqueezyScriptSelector)!.dispatchEvent(new Event('load'))

    await expect(retry).resolves.toBeUndefined()
    expect(window.createLemonSqueezy).toHaveBeenCalled()
  })
})
