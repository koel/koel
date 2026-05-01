import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import type { ModelFieldPair } from '@/__tests__/factory'
import { screen } from '@testing-library/vue'
import EmbedWidgetThumbnail from './EmbedWidgetThumbnail.vue'

describe('embedThumbnail.vue', () => {
  const h = createHarness()

  const renderComponent = (embeddable: Embeddable) => {
    const rendered = h.render(EmbedWidgetThumbnail, {
      props: {
        embeddable,
      },
    })

    return {
      ...rendered,
      embeddable,
    }
  }

  it.each<ModelFieldPair>([
    ['album', 'cover'],
    ['artist', 'image'],
    ['playlist', 'cover'],
    ['song', 'album_cover'],
    ['episode', 'episode_image'],
  ])('renders thumbnail for %s', (type, imageField) => {
    const { embeddable } = renderComponent(h.factory(type).make() as Embeddable)
    expect(screen.getByRole('img').getAttribute('src')).toBe(embeddable[imageField])
  })

  it('renders a default placeholder', () => {
    renderComponent(h.factory('playlist').make({ cover: null }))
    expect(screen.getByRole('img').getAttribute('src')).toContain('/resources/assets/img/covers/default.svg')
  })
})
