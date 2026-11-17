import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { settingStore } from '@/stores/settingStore'
import Component from './YouTubeApiKeyForm.vue'

describe('youTubeApiKeyForm.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      settingStore.state.youtube = undefined
    },
  })

  it('saves the API key', async () => {
    const updateMock = h.mock(settingStore, 'updateYouTubeApiKey').mockResolvedValue(undefined)
    h.render(Component)

    await h.type(screen.getByTestId('input'), 'youtube-key')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith('youtube-key')
  })

  it('removes the stored API key', async () => {
    settingStore.state.youtube = { has_api_key: true }
    const updateMock = h.mock(settingStore, 'updateYouTubeApiKey').mockResolvedValue(undefined)
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))

    expect(updateMock).toHaveBeenCalledWith(null)
  })

  it('offers no removal without a stored API key', () => {
    h.render(Component)
    expect(screen.queryByRole('button', { name: 'Remove' })).toBeNull()
  })
})
