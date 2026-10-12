import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { playableStore } from '@/stores/playableStore'
import { crossfadeService } from './crossfadeService'

describe('crossfadeService', () => {
  const h = createHarness()

  it('skips the crossfade when the next playable has no source URL yet', () => {
    h.mock(playableStore, 'getResolvedSourceUrl').mockReturnValue(undefined)

    expect(crossfadeService.start(h.factory('song').make(), 5, 7)).toBe(false)
    expect(crossfadeService.active).toBe(false)
  })
})
