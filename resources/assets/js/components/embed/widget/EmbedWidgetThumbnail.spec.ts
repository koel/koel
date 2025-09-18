import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import type { ModelToTypeMap } from '@/__tests__/factory'
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

  it.each<[keyof ModelToTypeMap, string]>([
    ['album', 'cover'],
    ['artist', 'image'],
    ['playlist', 'cover'],
    ['song', 'album_cover'],
    ['episode', 'episode_image'],
  ])('renders thumbnail for %s', (type, imageField) => {
    const { embeddable } = renderComponent(h.factory(type) as Embeddable)
    expect(screen.getByRole('img').getAttribute('src')).toBe(embeddable[imageField])
  })

  it('renders a default placeholder', () => {
    renderComponent(h.factory('playlist', { cover: null }))
    expect(screen.getByRole('img').getAttribute('src')).toContain('/resources/assets/img/covers/default.svg')
  })
})
