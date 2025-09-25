import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { cache } from '@/services/cache'
import { acl as service } from './acl'

describe('acl', () => {
  const h = createHarness()

  it('checks and caches permission', async () => {
    const getMock = h.mock(http, 'get').mockResolvedValue({
      allowed: true,
    })

    const allowed = await service.checkResourcePermission('album', 200, 'edit')

    expect(getMock).toHaveBeenCalledWith('acl/permissions/album/200/edit')
    expect(allowed).toBe(true)
    expect(cache.get(['permission', 'album', 200, 'edit'])).toBe(true)
  })

  it('gets permission from cache', async () => {
    const getMock = h.mock(http, 'get')
    cache.set(['permission', 'album', 200, 'edit'], true)

    const allowed = await service.checkResourcePermission('album', 200, 'edit')
    expect(getMock).not.toHaveBeenCalled()
    expect(allowed).toBe(true)
  })

  it('fetches assignable roles', async () => {
    const roles = [
      { id: 'admin', label: 'Admin', description: 'Full access to all system features' },
      { id: 'manager', label: 'Manager', description: 'Can edit content but has limited administrative privileges' },
      { id: 'user', label: 'User', description: 'Read-only access to content' },
    ]

    const getMock = h.mock(http, 'get').mockResolvedValue({ roles })

    const results = await service.fetchAssignableRoles()
    expect(getMock).toHaveBeenCalledWith('acl/assignable-roles')
    expect(results).toEqual(roles)
  })

  it('gets assignable roles from cache', async () => {
    const roles = [
      { id: 'admin', label: 'Admin', description: 'Full access to all system features' },
      { id: 'manager', label: 'Manager', description: 'Can edit content but has limited administrative privileges' },
      { id: 'user', label: 'User', description: 'Read-only access to content' },
    ]

    const cacheGetMock = h.mock(cache, 'remember').mockReturnValue(roles)
    const httpGetMock = h.mock(http, 'get')

    const results = await service.fetchAssignableRoles()

    expect(httpGetMock).not.toHaveBeenCalled()
    expect(cacheGetMock).toHaveBeenCalled()
    expect(results).toEqual(roles)
  })
})
