import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { DialogBoxStub, MessageToasterStub } from '@/__tests__/stubs'
import { userStore } from '@/stores/userStore'
import { copyText } from '@/utils/helpers'
import Component from './SubsonicCredentials.vue'

vi.mock('@/utils/helpers', async original => {
  const mod = await original<typeof import('@/utils/helpers')>()
  return { ...mod, copyText: vi.fn() }
})

describe('subsonicCredentials.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      h.actingAsUser()
      userStore.state.current.subsonic_api_key = 'original-key'
    },
  })

  it('renders the current key', async () => {
    h.render(Component)
    const input = await screen.findByRole<HTMLInputElement>('textbox')
    expect(input.value).toBe('original-key')
  })

  it('copies the key to the clipboard', async () => {
    h.mock(MessageToasterStub.value, 'success')
    h.render(Component)

    await h.user.click(await screen.findByRole('button', { name: /copy/i }))

    expect(copyText).toHaveBeenCalledWith('original-key')
  })

  it('regenerates when confirmed and surfaces the new key', async () => {
    h.mock(DialogBoxStub.value, 'confirm', true)
    h.mock(MessageToasterStub.value, 'success')
    h.mock(userStore, 'regenerateSubsonicApiKey').mockImplementation(async () => {
      userStore.state.current.subsonic_api_key = 'fresh-key'
      return 'fresh-key'
    })

    h.render(Component)
    await h.user.click(await screen.findByRole('button', { name: /regenerate key/i }))

    const input = await screen.findByRole<HTMLInputElement>('textbox')
    expect(input.value).toBe('fresh-key')
  })

  it('does not regenerate when the confirm is dismissed', async () => {
    h.mock(DialogBoxStub.value, 'confirm', false)
    const regenMock = h.mock(userStore, 'regenerateSubsonicApiKey')

    h.render(Component)
    await h.user.click(await screen.findByRole('button', { name: /regenerate key/i }))

    expect(regenMock).not.toHaveBeenCalled()
  })
})
