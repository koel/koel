import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { useThirdPartyServices } from './useThirdPartyServices'

describe('useThirdPartyServices', () => {
  createHarness()

  it('exposes service flags as refs matching store state', () => {
    const services = useThirdPartyServices()

    expect(services.useLastfm.value).toBe(commonStore.state.uses_last_fm)
    expect(services.useMusicBrainz.value).toBe(commonStore.state.uses_musicbrainz)
    expect(services.useYouTube.value).toBe(commonStore.state.uses_you_tube)
    expect(services.useAppleMusic.value).toBe(commonStore.state.uses_i_tunes)
    expect(services.useSpotify.value).toBe(commonStore.state.uses_spotify)
    expect(services.useTicketmaster.value).toBe(commonStore.state.uses_ticketmaster)
  })
})
