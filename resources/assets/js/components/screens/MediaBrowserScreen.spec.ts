import { describe, expect, it, vi } from 'vite-plus/test'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { mediaBrowser } from '@/services/mediaBrowser'
import Component from './MediaBrowserScreen.vue'

const router = vi.hoisted(() => ({
  changeRoute: (_route: { screen: ScreenName }) => {},
  go: vi.fn(),
  url: vi.fn((name: string, params?: Record<string, string>) => (params ? `${name}/${params.folder}` : name)),
}))

vi.mock('@/composables/useRouter', () => ({
  useRouter: () => ({
    onRouteChanged: (callback: (route: { screen: ScreenName }) => void) => (router.changeRoute = callback),
    getRouteParam: () => '',
    getCurrentScreen: () => 'MediaBrowser',
    onScreenActivated: (_screen: ScreenName, callback: Closure) => callback(),
    go: router.go,
    url: router.url,
  }),
}))

describe('mediaBrowserScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => router.go.mockClear(),
  })

  const makeFolder = (id: string): Folder => ({ type: 'folders', id, parent_id: null, name: id, is_uploads: false })

  const mockBrowsing = (current: Folder | null, ancestors: Folder[] = []) =>
    h.mock(mediaBrowser, 'browse').mockResolvedValue({
      current,
      ancestors,
      subfolders: [],
      songs: h.factory('song').make(2),
      nextCursor: null,
    })

  const renderFolder = async (current: Folder | null, ancestors: Folder[] = []) => {
    commonStore.state.song_length = 10
    mockBrowsing(current, ancestors)

    h.render(Component)
    await h.tick(2)
  }

  it('shows empty state when library has no songs', () => {
    commonStore.state.song_length = 0
    mockBrowsing(null)

    h.render(Component)
    screen.getByText('No files found.')
  })

  it('opens the parent folder with Backspace', async () => {
    await renderFolder(makeFolder('child'), [makeFolder('grandparent'), makeFolder('parent')])

    await fireEvent.keyDown(screen.getAllByRole('article')[0], { key: 'Backspace' })

    expect(router.go).toHaveBeenCalledWith('media-browser/parent')
  })

  it('opens the library root with Alt+Up from a top-level folder', async () => {
    await renderFolder(makeFolder('top'))

    await fireEvent.keyDown(screen.getAllByRole('article')[0], { key: 'ArrowUp', altKey: true })

    expect(router.go).toHaveBeenCalledWith('media-browser')
  })

  it('does nothing on Backspace at the library root', async () => {
    await renderFolder(null)

    await fireEvent.keyDown(screen.getAllByRole('article')[0], { key: 'Backspace' })

    expect(router.go).not.toHaveBeenCalled()
  })

  it('keeps the list focused after opening another folder', async () => {
    await renderFolder(makeFolder('top'))
    screen.getAllByRole('article')[1].focus()

    router.changeRoute({ screen: 'MediaBrowser' })
    await h.tick(3)

    expect(document.activeElement).toBe(document.querySelector('.virtual-scroller'))
  })

  it('focuses the list after opening a folder from the breadcrumbs', async () => {
    await renderFolder(makeFolder('child'), [makeFolder('parent')])
    screen.getByRole('link', { name: 'parent' }).focus()

    router.changeRoute({ screen: 'MediaBrowser' })
    await h.tick(3)

    expect(document.activeElement).toBe(document.querySelector('.virtual-scroller'))
  })
})
