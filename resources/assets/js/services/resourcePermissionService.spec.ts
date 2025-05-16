import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { http } from '@/services/http'
import { cache } from '@/services/cache'
import { resourcePermissionService as service } from './resourcePermissionService'

new class extends UnitTestCase {
  protected test () {
    it('checks and caches permission', async () => {
      const getMock = this.mock(http, 'get').mockResolvedValue({
        allowed: true,
      })

      const allowed = await service.check('album', 200, 'edit')

      expect(getMock).toHaveBeenCalledWith('permissions/album/200/edit')
      expect(allowed).toBe(true)
      expect(cache.get(['permission', 'album', 200, 'edit'])).toBe(true)
    })

    it('gets permission from cache', async () => {
      const getMock = this.mock(http, 'get')
      cache.set(['permission', 'album', 200, 'edit'], true)

      const allowed = await service.check('album', 200, 'edit')
      expect(getMock).not.toHaveBeenCalled()
      expect(allowed).toBe(true)
    })
  }
}
