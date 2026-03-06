import isMobile from 'ismobilejs'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { http } from '@/services/http'
import { userStore } from '@/stores/userStore'
import { preferenceStore } from '@/stores/preferenceStore'
import { playlistStore } from '@/stores/playlistStore'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { settingStore } from '@/stores/settingStore'
import { queueStore } from '@/stores/queueStore'
import { themeStore } from '@/stores/themeStore'

describe('commonStore', () => {
  const h = createHarness()

  const createApiResponse = (overrides = {}) => ({
    current_user: h.factory('user'),
    playlists: [],
    playlist_folders: [],
    settings: {},
    queue_state: { type: 'queue-states', songs: [], current_song: null, playback_position: 0 },
    uses_you_tube: true,
    supports_transcoding: true,
    current_theme: null,
    ...overrides,
  })

  it('fetches and populates state from API', async () => {
    const response = createApiResponse({ current_version: '7.0.0', media_path_set: true })
    h.mock(http, 'get').mockResolvedValue(response)

    await commonStore.init()

    expect(commonStore.state.current_version).toBe('7.0.0')
    expect(commonStore.state.media_path_set).toBe(true)
  })

  it('disables YouTube on mobile', async () => {
    isMobile.any = true
    h.mock(http, 'get').mockResolvedValue(createApiResponse({ uses_you_tube: true }))

    await commonStore.init()

    expect(commonStore.state.uses_you_tube).toBe(false)
  })

  it('keeps YouTube enabled on desktop', async () => {
    isMobile.any = false
    h.mock(http, 'get').mockResolvedValue(createApiResponse({ uses_you_tube: true }))

    await commonStore.init()

    expect(commonStore.state.uses_you_tube).toBe(true)
  })

  it('enables transcoding only on mobile', async () => {
    isMobile.any = true
    h.mock(http, 'get').mockResolvedValue(createApiResponse({ supports_transcoding: true }))

    await commonStore.init()

    expect(commonStore.state.supports_transcoding).toBe(true)
  })

  it('disables transcoding on desktop', async () => {
    isMobile.any = false
    h.mock(http, 'get').mockResolvedValue(createApiResponse({ supports_transcoding: true }))

    await commonStore.init()

    expect(commonStore.state.supports_transcoding).toBe(false)
  })

  it('initializes dependent stores', async () => {
    const userInitMock = h.mock(userStore, 'init')
    const prefInitMock = h.mock(preferenceStore, 'init')
    const playlistInitMock = h.mock(playlistStore, 'init')
    const folderInitMock = h.mock(playlistFolderStore, 'init')
    const settingInitMock = h.mock(settingStore, 'init')
    const queueInitMock = h.mock(queueStore, 'init')
    const themeInitMock = h.mock(themeStore, 'init')

    const response = createApiResponse()
    h.mock(http, 'get').mockResolvedValue(response)

    await commonStore.init()

    expect(userInitMock).toHaveBeenCalled()
    expect(prefInitMock).toHaveBeenCalled()
    expect(playlistInitMock).toHaveBeenCalled()
    expect(folderInitMock).toHaveBeenCalled()
    expect(settingInitMock).toHaveBeenCalled()
    expect(queueInitMock).toHaveBeenCalled()
    expect(themeInitMock).toHaveBeenCalled()
  })

  it('returns state from init', async () => {
    h.mock(http, 'get').mockResolvedValue(createApiResponse())
    const result = await commonStore.init()

    expect(result).toBe(commonStore.state)
  })
})
