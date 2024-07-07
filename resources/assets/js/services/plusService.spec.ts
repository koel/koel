import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { http } from '@/services'
import { plusService as service } from './plusService'

new class extends UnitTestCase {
  protected test () {
    it('activates license', async () => {
      const postMock = this.mock(http, 'post').mockResolvedValue({})

      await service.activateLicense('abc123')

      expect(postMock).toHaveBeenCalledWith('licenses/activate', { key: 'abc123' })
    })
  }
}
