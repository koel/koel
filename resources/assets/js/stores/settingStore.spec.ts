import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { commonStore } from '@/stores/commonStore'
import { settingStore } from '@/stores/settingStore'

describe('settingStore', () => {
  const h = createHarness()

  it('initializes the store', () => {
    settingStore.init({ media_path: '/media/path' })
    expect(settingStore.state.media_path).toEqual('/media/path')
  })

  it('updates the media path', async () => {
    const putMock = h.mock(http, 'put')
    await settingStore.updateMediaPath('/dev/null')

    expect(putMock).toHaveBeenCalledWith('settings/media-path', { path: '/dev/null' })
    expect(settingStore.state.media_path).toEqual('/dev/null')
  })

  it('updates branding', async () => {
    const putMock = h.mock(http, 'put')
    await settingStore.updateBranding({
      name: 'Koel',
    })

    expect(putMock).toHaveBeenCalledWith('settings/branding', { name: 'Koel' })
  })

  it.each<[AiSettings, boolean]>([
    [{ enabled: true, provider: 'openai', has_api_key: true }, true],
    [{ enabled: false, provider: 'openai', has_api_key: true }, false],
    [{ enabled: true, provider: 'openai', has_api_key: false }, false],
  ])('shows or hides the AI assistant after saving %o', async (saved, usesAi) => {
    commonStore.state.uses_ai = !usesAi
    h.mock(http, 'put').mockResolvedValue(saved)

    await settingStore.updateAi({ enabled: saved.enabled, provider: 'openai' })

    expect(commonStore.state.uses_ai).toBe(usesAi)
  })
})
