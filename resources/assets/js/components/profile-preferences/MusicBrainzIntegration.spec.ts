import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import Component from './MusicBrainzIntegration.vue'

describe('musicBrainzIntegration.vue', () => {
  const h = createHarness()

  it.each<[boolean, boolean]>([[false, false], [false, true], [true, false], [true, true]])(
    'renders proper content with MusicBrainz integration status %s, current user admin status %s',
    (useMusicBrainz, isAdmin) => {
      commonStore.state.uses_musicbrainz = useMusicBrainz

      if (isAdmin) {
        h.actingAsAdmin()
      } else {
        h.actingAsUser()
      }

      expect(h.render(Component).html()).toMatchSnapshot()
    },
  )
})
