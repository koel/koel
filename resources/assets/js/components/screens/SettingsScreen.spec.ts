import Router from '@/router'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { settingStore } from '@/stores'
import { screen, waitFor } from '@testing-library/vue'
import { DialogBoxStub } from '@/__tests__/stubs'
import SettingsScreen from './SettingsScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.render(SettingsScreen).html()).toMatchSnapshot())

    it('submits the settings form', async () => {
      const updateMock = this.mock(settingStore, 'update')
      const goMock = this.mock(Router, 'go')

      settingStore.state.media_path = ''
      this.render(SettingsScreen)

      await this.type(screen.getByPlaceholderText('/path/to/your/music'), '/media')
      await this.user.click(screen.getByTestId('submit'))

      await waitFor(() => {
        expect(updateMock).toHaveBeenCalledWith({ media_path: '/media' })
        expect(goMock).toHaveBeenCalledWith('home')
      })
    })

    it('confirms upon media path change', async () => {
      const updateMock = this.mock(settingStore, 'update')
      const goMock = this.mock(Router, 'go')
      const confirmMock = this.mock(DialogBoxStub.value, 'confirm')

      settingStore.state.media_path = '/old'
      this.render(SettingsScreen)

      await this.type(screen.getByPlaceholderText('/path/to/your/music'), '/new')
      await this.user.click(screen.getByTestId('submit'))

      await waitFor(() => {
        expect(updateMock).not.toHaveBeenCalled()
        expect(goMock).not.toHaveBeenCalled()
        expect(confirmMock).toHaveBeenCalled()
      })
    })
  }
}
