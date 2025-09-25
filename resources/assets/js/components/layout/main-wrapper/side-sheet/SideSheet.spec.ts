import type { Ref } from 'vue'
import { ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore } from '@/stores/preferenceStore'
import { CurrentStreamableKey } from '@/symbols'
import { eventBus } from '@/utils/eventBus'
import Component from './SideSheet.vue'

describe('sideSheet.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      // disable getting and saving the preferences
      h.mock(preferenceStore, 'update')
    },
  })

  const renderComponent = (songRef: Ref<Song | null> = ref(null)) => {
    const artist = h.factory('artist')
    const resolveArtistMock = h.mock(artistStore, 'resolve').mockResolvedValue(artist)

    const album = h.factory('album')
    const resolveAlbumMock = h.mock(albumStore, 'resolve').mockResolvedValue(album)

    const rendered = h.render(Component, {
      global: {
        stubs: {
          ProfileAvatar: h.stub('profile-avatar'),
          LyricsPane: h.stub('lyrics'),
          AlbumInfo: h.stub('album-info'),
          ArtistInfo: h.stub('artist-info'),
          YouTubeVideoList: h.stub('youtube-video-list'),
        },
        provide: {
          [<symbol>CurrentStreamableKey]: songRef,
        },
      },
    })

    return {
      rendered,
      resolveArtistMock,
      resolveAlbumMock,
    }
  }

  it('renders without a current playable', () => expect(renderComponent().rendered.html()).toMatchSnapshot())

  it('gets active tab from the preference', async () => {
    preferenceStore.active_extra_panel_tab = 'Lyrics'
    renderComponent(ref(h.factory('song')))

    await waitFor(() => {
      expect(screen.getByTestId<HTMLElement>('side-sheet-lyrics').style.display).toBe('')
    })
  })

  it('activates and sets active tab to the preference', async () => {
    preferenceStore.active_extra_panel_tab = 'Lyrics'
    renderComponent(ref(h.factory('song')))
    await h.tick()
    await h.user.click(screen.getByTestId('side-sheet-album-tab-header'))

    await waitFor(() => {
      expect(screen.getByTestId<HTMLElement>('side-sheet-lyrics').style.display).toBe('none')
      expect(screen.getByTestId<HTMLElement>('side-sheet-album').style.display).toBe('')
      expect(preferenceStore.active_extra_panel_tab).toBe('Album')
    })
  })

  it('resolves album and fetches album info for the current playable', async () => {
    preferenceStore.active_extra_panel_tab = 'Album'

    const songRef = ref<Song | null>(null)

    const { resolveAlbumMock } = renderComponent(songRef)

    // trigger the side sheet to show album info
    songRef.value = h.factory('song')

    await waitFor(() => {
      screen.getByTestId('side-sheet-album')
      screen.getByTestId('album-info')
      expect(resolveAlbumMock).toHaveBeenCalledWith(songRef.value?.album_id)
    })
  })

  it('resolves artist and fetches artist info for the current playable', async () => {
    preferenceStore.active_extra_panel_tab = 'Artist'

    const songRef = ref<Song | null>(null)

    const { resolveArtistMock } = renderComponent(songRef)

    // trigger the side sheet to show artist info
    songRef.value = h.factory('song')

    await waitFor(() => {
      screen.getByTestId('side-sheet-artist')
      screen.getByTestId('artist-info')
      expect(resolveArtistMock).toHaveBeenCalledWith(songRef.value?.artist_id)
    })
  })

  it('shows About Koel model', async () => {
    const emitMock = h.mock(eventBus, 'emit')
    renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'About Koel' }))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_ABOUT_KOEL')
  })

  it('notifies new version', async () => {
    commonStore.state.current_version = 'v1.0.0'
    commonStore.state.latest_version = 'v1.0.1'
    h.actingAsAdmin()
    renderComponent()
    screen.getByRole('button', { name: 'New version available!' })
  })

  it('logs out', async () => {
    const emitMock = h.mock(eventBus, 'emit')
    renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Log out' }))

    expect(emitMock).toHaveBeenCalledWith('LOG_OUT')
  })
})
