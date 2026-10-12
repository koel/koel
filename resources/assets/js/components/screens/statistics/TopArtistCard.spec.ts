import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { useBranding } from '@/composables/useBranding'
import Component from './TopArtistCard.vue'

describe('topArtistCard.vue', () => {
  const h = createHarness()

  const renderCard = (image: string, albumCover: string | null) =>
    h.render(Component, {
      props: {
        artist: h.factory('artist').make({ image }),
        rank: 1,
        listeningTime: 600,
        albumCover,
      },
    })

  const artworkOf = (container: Element) => container.querySelector('img')?.getAttribute('src')

  it.each<[string, string, string | null, () => string]>([
    [
      'the artist photo',
      'https://koel.test/photo.webp',
      'https://koel.test/cover.webp',
      () => 'https://koel.test/photo.webp',
    ],
    ['the album cover', '', 'https://koel.test/cover.webp', () => 'https://koel.test/cover.webp'],
    ['the default cover', '', null, () => useBranding().cover],
  ])('shows %s', (_, image, albumCover, expected) => {
    const { container } = renderCard(image, albumCover)

    expect(artworkOf(container)).toBe(expected())
  })
})
