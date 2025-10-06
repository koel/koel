import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
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
})
