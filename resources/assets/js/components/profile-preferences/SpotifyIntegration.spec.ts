import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import Component from './SpotifyIntegration.vue'

describe('spotifyIntegration.vue', () => {
  const h = createHarness()

  it.each<[boolean, boolean]>([[false, false], [false, true], [true, false], [true, true]])(
    'renders proper content with Spotify integration status %s, current user admin status %s',
    (useSpotify, isAdmin) => {
      commonStore.state.uses_spotify = useSpotify

      if (isAdmin) {
        h.actingAsAdmin()
      } else {
        h.actingAsUser()
      }

      expect(h.render(Component).html()).toMatchSnapshot()
    },
  )
})
