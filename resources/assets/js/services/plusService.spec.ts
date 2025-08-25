import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { plusService as service } from './plusService'

describe('plusService', () => {
  const h = createHarness()

  it('activates license', async () => {
    const postMock = h.mock(http, 'post').mockResolvedValue({})

    await service.activateLicense('abc123')

    expect(postMock).toHaveBeenCalledWith('licenses/activate', { key: 'abc123' })
  })
})
