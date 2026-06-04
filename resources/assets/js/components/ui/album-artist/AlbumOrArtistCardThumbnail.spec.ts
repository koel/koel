import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AlbumOrArtistCardThumbnail.vue'

describe('albumOrArtistCardThumbnail.vue', () => {
  const h = createHarness()

  it('renders an album thumbnail with cover image', () => {
    const album = h.factory('album').make({ cover: 'https://example.test/cover.jpg' })
    h.render(Component, { props: { entity: album } })

    const img = screen.getByAltText(album.name) as HTMLImageElement
    expect(img.src).toBe('https://example.test/cover.jpg')
  })

  it('renders an artist thumbnail with image', () => {
    const artist = h.factory('artist').make({ image: 'https://example.test/artist.jpg' })
    h.render(Component, { props: { entity: artist } })

    const img = screen.getByAltText(artist.name) as HTMLImageElement
    expect(img.src).toBe('https://example.test/artist.jpg')
  })

  it('emits toggle-favorite when the heart is clicked', async () => {
    const album = h.factory('album').make({ favorite: true })
    const { emitted } = h.render(Component, { props: { entity: album } })

    await h.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))

    expect(emitted('toggle-favorite')).toHaveLength(1)
  })

  it('emits context-menu when the more-actions button is clicked', async () => {
    const album = h.factory('album').make()
    const { emitted } = h.render(Component, { props: { entity: album } })

    await h.user.click(screen.getByRole('button', { name: 'More actions' }))

    expect(emitted('context-menu')).toHaveLength(1)
  })

  it('renders the play button with the correct aria label for albums', () => {
    const album = h.factory('album').make({ name: 'Master of Puppets' })
    h.render(Component, { props: { entity: album } })

    screen.getByRole('button', { name: 'Play all songs in the album Master of Puppets' })
  })

  it('renders the play button with the correct aria label for artists', () => {
    const artist = h.factory('artist').make({ name: 'Metallica' })
    h.render(Component, { props: { entity: artist } })

    screen.getByRole('button', { name: 'Play all songs by Metallica' })
  })
})
