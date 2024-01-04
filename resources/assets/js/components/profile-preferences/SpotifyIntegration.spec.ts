import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore } from '@/stores'
import SpotifyIntegration from './SpotifyIntegration.vue'

new class extends UnitTestCase {
  protected test () {
    it.each<[boolean, boolean]>([[false, false], [false, true], [true, false], [true, true]])
    ('renders proper content with Spotify integration status %s, current user admin status %s',
      (useSpotify, isAdmin) => {
        commonStore.state.uses_spotify = useSpotify

        if (isAdmin) {
          this.actingAsAdmin()
        } else {
          this.actingAs()
        }

        expect(this.render(SpotifyIntegration).html()).toMatchSnapshot();
      }
    )
  }
}
