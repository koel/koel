import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import Component from './LastfmIntegration.vue'

describe('lastfmIntegration.vue', () => {
  const h = createHarness()

  it.each<[boolean, boolean]>([[false, false], [false, true], [true, false], [true, true]])(
    'renders proper content with Last.fm integration status %s, current user admin status %s',
    (useLastfm, isAdmin) => {
      commonStore.state.uses_last_fm = useLastfm

      if (isAdmin) {
        h.actingAsAdmin()
      } else {
        h.actingAsUser()
      }

      expect(h.render(Component).html()).toMatchSnapshot()
    },
  )
})
