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
      const goMock = this.mock(this.router, 'go')

      settingStore.state.media_path = ''
      this.render(SettingsScreen)

      await this.type(screen.getByLabelText('Media Path'), '/media')
      await this.user.click(screen.getByRole('button', { name: 'Scan' }))

      await waitFor(() => {
        expect(updateMock).toHaveBeenCalledWith({ media_path: '/media' })
        expect(goMock).toHaveBeenCalledWith('home')
      })
    })

    it('confirms upon media path change', async () => {
      const updateMock = this.mock(settingStore, 'update')
      const goMock = this.mock(this.router, 'go')
      const confirmMock = this.mock(DialogBoxStub.value, 'confirm')

      settingStore.state.media_path = '/old'
      this.render(SettingsScreen)

      await this.type(screen.getByLabelText('Media Path'), '/new')
      await this.user.click(screen.getByRole('button', { name: 'Scan' }))

      await waitFor(() => {
        expect(updateMock).not.toHaveBeenCalled()
        expect(goMock).not.toHaveBeenCalled()
        expect(confirmMock).toHaveBeenCalled()
      })
    })
  }
}
