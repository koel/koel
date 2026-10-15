import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { useBranding } from '@/composables/useBranding'
import Component from './TopAlbumCard.vue'

describe('topAlbumCard.vue', () => {
  const h = createHarness()

  const renderCard = (cover: string) =>
    h.render(Component, {
      props: {
        album: h.factory('album').make({ cover }),
        rank: 1,
        listeningTime: 600,
      },
    })

  const artworkOf = (container: Element) => container.querySelector('img')?.getAttribute('src')

  it.each<[string, string, () => string]>([
    ['the album cover', 'https://koel.test/cover.webp', () => 'https://koel.test/cover.webp'],
    ['the default cover', '', () => useBranding().cover],
  ])('shows %s', (_, cover, expected) => {
    const { container } = renderCard(cover)

    expect(artworkOf(container)).toBe(expected())
  })
})
