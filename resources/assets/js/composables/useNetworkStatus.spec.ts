import { describe, expect, it } from 'vite-plus/test'
import { useNetworkStatus } from './useNetworkStatus'

describe('useNetworkStatus', () => {
  it('returns a shared reactive online ref', () => {
    const { online: online1 } = useNetworkStatus()
    const { online: online2 } = useNetworkStatus()
    expect(online1).toBe(online2)
  })

  it('reflects the current online state', () => {
    const { online } = useNetworkStatus()
    expect(typeof online.value).toBe('boolean')
  })
})
