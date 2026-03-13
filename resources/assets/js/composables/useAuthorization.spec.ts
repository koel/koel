import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { useAuthorization } from './useAuthorization'
import { userStore } from '@/stores/userStore'

describe('useAuthorization', () => {
  const h = createHarness()

  it('returns a ref to the current user', () => {
    const user = h.factory('user', { name: 'Alice' }) as CurrentUser
    userStore.state.current = user

    const { currentUser } = useAuthorization()
    expect(currentUser.value.name).toBe('Alice')
  })

  it('reacts to user changes', () => {
    const { currentUser } = useAuthorization()

    userStore.state.current = h.factory('user', { name: 'Bob' }) as CurrentUser
    expect(currentUser.value.name).toBe('Bob')
  })
})
