import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SettingsScreen from './SettingsScreen.vue'
import { settingStore } from '@/stores'
import { fireEvent, waitFor } from '@testing-library/vue'
import router from '@/router'
import { alerts } from '@/utils'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.render(SettingsScreen).html()).toMatchSnapshot())

    it('submits the settings form', async () => {
      const updateMock = this.mock(settingStore, 'update')
      const goMock = this.mock(router, 'go')

      settingStore.state.media_path = ''
      const { getByLabelText, getByText } = this.render(SettingsScreen)

      await fireEvent.update(getByLabelText('Media Path'), '/media')
      await fireEvent.click(getByText('Scan'))

      await waitFor(() => {
        expect(updateMock).toHaveBeenCalledWith({ media_path: '/media' })
        expect(goMock).toHaveBeenCalledWith('home')
      })
    })

    it('confirms upon media path change', async () => {
      const updateMock = this.mock(settingStore, 'update')
      const goMock = this.mock(router, 'go')
      const confirmMock = this.mock(alerts, 'confirm')

      settingStore.state.media_path = '/old'
      const { getByLabelText, getByText } = this.render(SettingsScreen)

      await fireEvent.update(getByLabelText('Media Path'), '/new')
      await fireEvent.click(getByText('Scan'))

      await waitFor(() => {
        expect(updateMock).not.toHaveBeenCalled()
        expect(goMock).not.toHaveBeenCalled()
        expect(confirmMock).toHaveBeenCalled()
      })
    })
  }
}
