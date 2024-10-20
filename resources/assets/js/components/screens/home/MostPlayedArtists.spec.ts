import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { overviewStore } from '@/stores/overviewStore'
import MostPlayedArtists from './MostPlayedArtists.vue'

new class extends UnitTestCase {
  protected test () {
    it('displays the artists', () => {
      overviewStore.state.mostPlayedArtists = factory('artist', 6)
      expect(this.render(MostPlayedArtists, {
        global: {
          stubs: {
            ArtistCard: this.stub('artist-card'),
          },
        },
      }).getAllByTestId('artist-card')).toHaveLength(6)
    })
  }
}
