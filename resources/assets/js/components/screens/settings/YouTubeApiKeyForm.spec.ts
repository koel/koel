import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { DialogBoxStub } from '@/__tests__/stubs'
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

  it('disables Remove while the removal is pending', async () => {
    settingStore.state.youtube = { has_api_key: true }
    h.mock(settingStore, 'updateYouTubeApiKey').mockReturnValue(new Promise(() => {}))
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))

    expect(screen.getByRole('button', { name: 'Remove' })).toHaveProperty('disabled', true)
  })

  it('shows an error when the removal fails', async () => {
    settingStore.state.youtube = { has_api_key: true }
    h.mock(settingStore, 'updateYouTubeApiKey').mockRejectedValue(new Error('Removal failed'))
    const showErrorDialogMock = h.mock(DialogBoxStub.value, 'error')
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))

    await waitFor(() => expect(showErrorDialogMock).toHaveBeenCalled())
    expect(screen.getByRole('button', { name: 'Remove' })).toHaveProperty('disabled', false)
  })

  it('offers no removal without a stored API key', () => {
    h.render(Component)
    expect(screen.queryByRole('button', { name: 'Remove' })).toBeNull()
  })
})
