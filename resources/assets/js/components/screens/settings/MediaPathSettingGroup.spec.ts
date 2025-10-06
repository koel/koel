import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { settingStore } from '@/stores/settingStore'
import Router from '@/router'
import { screen, waitFor } from '@testing-library/vue'
import { DialogBoxStub } from '@/__tests__/stubs'
import Component from './MediaPathSettingGroup.vue'

describe('mediaPathSettingGroup.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    return h.render(Component)
  }

  it('submits the settings form', async () => {
    const updateMock = h.mock(settingStore, 'updateMediaPath')
    const goMock = h.mock(Router, 'go')

    settingStore.state.media_path = ''
    renderComponent()

    await h.type(screen.getByPlaceholderText('/path/to/your/music'), '/media')
    await h.user.click(screen.getByTestId('submit'))

    await waitFor(() => {
      expect(updateMock).toHaveBeenCalledWith('/media')
      expect(goMock).toHaveBeenCalledWith('/#/home', true)
    })
  })

  it('confirms upon media path change', async () => {
    const updateMock = h.mock(settingStore, 'updateMediaPath')
    const goMock = h.mock(Router, 'go')
    const confirmMock = h.mock(DialogBoxStub.value, 'confirm')

    settingStore.state.media_path = '/old'
    h.render(Component)

    await h.type(screen.getByPlaceholderText('/path/to/your/music'), '/new')
    await h.user.click(screen.getByTestId('submit'))

    await waitFor(() => {
      expect(updateMock).not.toHaveBeenCalled()
      expect(goMock).not.toHaveBeenCalled()
      expect(confirmMock).toHaveBeenCalled()
    })
  })
})
