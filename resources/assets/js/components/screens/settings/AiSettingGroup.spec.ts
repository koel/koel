import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { DialogBoxStub } from '@/__tests__/stubs'
import { settingStore } from '@/stores/settingStore'
import Component from './AiSettingGroup.vue'

describe('aiSettingGroup.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      settingStore.state.ai = undefined
    },
  })

  const submit = async () => await h.user.click(screen.getByRole('button', { name: 'Save' }))

  it('turns the assistant on with a provider and a key', async () => {
    const updateMock = h.mock(settingStore, 'updateAi').mockResolvedValue(undefined)
    h.render(Component)

    await h.user.click(screen.getByRole('checkbox'))
    await h.user.selectOptions(screen.getByRole('combobox'), 'anthropic')
    await h.type(screen.getByTestId('input'), 'sk-ant-test')
    await submit()

    expect(updateMock).toHaveBeenCalledWith({ enabled: true, provider: 'anthropic', api_key: 'sk-ant-test' })
  })

  it('keeps the saved key when the field is left empty', async () => {
    settingStore.state.ai = {
      source: 'organization',
      enabled: true,
      provider: 'openai',
      has_api_key: true,
      server_setup_usable: false,
    }
    const updateMock = h.mock(settingStore, 'updateAi').mockResolvedValue(undefined)
    h.render(Component)

    await h.user.click(screen.getByRole('checkbox'))
    await submit()

    expect(updateMock).toHaveBeenCalledWith({ enabled: false, provider: 'openai' })
  })

  it('asks for a new key when the provider changes', async () => {
    settingStore.state.ai = {
      source: 'organization',
      enabled: true,
      provider: 'openai',
      has_api_key: true,
      server_setup_usable: false,
    }
    h.render(Component)

    expect((screen.getByTestId('input') as HTMLInputElement).required).toBe(false)

    await h.user.selectOptions(screen.getByRole('combobox'), 'anthropic')

    expect((screen.getByTestId('input') as HTMLInputElement).required).toBe(true)
  })

  it('says when the server configuration is in effect', () => {
    settingStore.state.ai = {
      source: 'environment',
      enabled: true,
      provider: 'ollama',
      has_api_key: false,
      server_setup_usable: true,
    }
    h.render(Component)

    screen.getByTestId('server-configuration-notice')
    expect((screen.getByTestId('input') as HTMLInputElement).required).toBe(true)
  })

  it('says nothing about the server when the organization has its own setup', () => {
    settingStore.state.ai = {
      source: 'organization',
      enabled: false,
      provider: 'openai',
      has_api_key: true,
      server_setup_usable: false,
    }
    h.render(Component)

    expect(screen.queryByTestId('server-configuration-notice')).toBeNull()
  })

  it('goes back to the server setup after confirming', async () => {
    settingStore.state.ai = {
      source: 'organization',
      enabled: true,
      provider: 'openai',
      has_api_key: true,
      server_setup_usable: true,
    }
    const removeMock = h.mock(settingStore, 'removeAi').mockResolvedValue(undefined)
    h.mock(DialogBoxStub.value, 'confirm').mockResolvedValueOnce(true).mockResolvedValueOnce(false)
    h.render(Component)

    await h.user.click(screen.getByTestId('use-server-setup'))

    await waitFor(() => expect(removeMock).toHaveBeenCalled())
  })

  it('offers no way back to the server setup when the organization has none of its own', () => {
    settingStore.state.ai = {
      source: 'environment',
      enabled: false,
      provider: null,
      has_api_key: false,
      server_setup_usable: false,
    }
    h.render(Component)

    expect(screen.queryByTestId('use-server-setup')).toBeNull()
  })
})
