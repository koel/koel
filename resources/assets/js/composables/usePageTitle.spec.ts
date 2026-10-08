import { afterEach, describe, expect, it } from 'vite-plus/test'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { usePageTitle } from './usePageTitle'

describe('usePageTitle', () => {
  const h = createHarness()

  afterEach(() => usePageTitle().setNowPlayingTitle(null))

  const DocumentTitle = defineComponent({
    setup: () => usePageTitle().syncDocumentTitle(),
    template: '<div />',
  })

  const AlbumTitle = defineComponent({
    setup: () => usePageTitle().useScreenTitle('Album', () => 'Holy Diver'),
    template: '<div />',
  })

  const albumPath = () => `/albums/${h.factory('album').make().id}`

  it('uses the route title', () => {
    h.visit('/albums')
    h.render(DocumentTitle)

    expect(document.title).toBe('Albums – Koel')
  })

  it('uses the title a screen gives', () => {
    h.visit(albumPath())
    h.render(AlbumTitle)
    h.render(DocumentTitle)

    expect(document.title).toBe('Holy Diver – Koel')
  })

  it('drops the screen title once the screen is gone', async () => {
    h.visit(albumPath())
    const { unmount } = h.render(AlbumTitle)
    h.render(DocumentTitle)

    unmount()
    await h.tick()

    expect(document.title).toBe('Koel')
  })

  it('shows what is playing over the screen title', async () => {
    h.visit('/albums')
    h.render(DocumentTitle)

    usePageTitle().setNowPlayingTitle('Rainbow in the Dark')
    await h.tick()

    expect(document.title).toBe('Rainbow in the Dark ♫ Koel')
  })
})
