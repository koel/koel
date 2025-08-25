import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AlbumOrArtistCard.vue'

describe('albumOrArtistCard.vue', () => {
  const h = createHarness()

  it('emits events on user actions', async () => {
    const { emitted } = h.render(Component, {
      props: {
        entity: h.factory('album'),
      },
    })

    const component = screen.getByTestId('artist-album-card')
    await h.trigger(component, 'dblClick')
    expect(emitted().dblclick).toBeTruthy()

    await h.trigger(component, 'dragStart')
    expect(emitted().dragstart).toBeTruthy()

    await h.trigger(component, 'contextMenu')
    expect(emitted().contextmenu).toBeTruthy()
  })
})
