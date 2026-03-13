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

  it('exposes isPlus as computed', () => {
    const { isPlus } = useKoelPlus()
    expect(isPlus.value).toBe(true)
  })

  it('exposes license info', () => {
    const { license } = useKoelPlus()
    expect(license.shortKey).toBe('ABC-123')
    expect(license.customerName).toBe('John Doe')
    expect(license.customerEmail).toBe('john@example.com')
  })

  it('builds checkout URL from product id', () => {
    const { checkoutUrl } = useKoelPlus()
    expect(checkoutUrl.value).toContain('prod_xyz')
    expect(checkoutUrl.value).toContain('store.koel.dev/checkout')
  })
})
