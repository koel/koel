import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
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
    settingStore.state.ai = { enabled: true, provider: 'openai', has_api_key: true }
    const updateMock = h.mock(settingStore, 'updateAi').mockResolvedValue(undefined)
    h.render(Component)

    await h.user.click(screen.getByRole('checkbox'))
    await submit()

    expect(updateMock).toHaveBeenCalledWith({ enabled: false, provider: 'openai' })
  })

  it('asks for a new key when the provider changes', async () => {
    settingStore.state.ai = { enabled: true, provider: 'openai', has_api_key: true }
    h.render(Component)

    expect((screen.getByTestId('input') as HTMLInputElement).required).toBe(false)

    await h.user.selectOptions(screen.getByRole('combobox'), 'anthropic')

    expect((screen.getByTestId('input') as HTMLInputElement).required).toBe(true)
  })
})
