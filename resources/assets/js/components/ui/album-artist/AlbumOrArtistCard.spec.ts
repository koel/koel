import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import ArtistAlbumCard from './AlbumOrArtistCard.vue'

new class extends UnitTestCase {
  protected test () {
    it('emits events on user actions', async () => {
      const { emitted } = this.render(ArtistAlbumCard, {
        props: {
          entity: factory('album'),
        },
      })

      const component = screen.getByTestId('artist-album-card')
      await this.trigger(component, 'dblClick')
      expect(emitted().dblclick).toBeTruthy()

      await this.trigger(component, 'dragStart')
      expect(emitted().dragstart).toBeTruthy()

      await this.trigger(component, 'contextMenu')
      expect(emitted().contextmenu).toBeTruthy()
    })
  }
}
