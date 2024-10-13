import { expect, it } from 'vitest'
import { commonStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { http } from '@/services'
import { screen, waitFor } from '@testing-library/vue'
import AboutKoelModel from './AboutKoelModal.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => {
      commonStore.state.current_version = 'v0.0.0'
      commonStore.state.latest_version = 'v0.0.0'

      expect(this.renderComponent().html()).toMatchSnapshot()
    })

    it('shows new version', () => {
      commonStore.state.current_version = 'v1.0.0'
      commonStore.state.latest_version = 'v1.0.1'
      this.beAdmin().renderComponent().getByTestId('new-version-about')
    })

    it('shows demo notation', async () => {
      const getMock = this.mock(http, 'get').mockResolvedValue([])
      window.IS_DEMO = true

      this.renderComponent()

      await waitFor(() => {
        screen.getByTestId('demo-credits')
        expect(getMock).toHaveBeenCalledWith('demo/credits')
      })

      window.IS_DEMO = false
    })
  }

  private renderComponent () {
    return this.render(AboutKoelModel, {
      global: {
        stubs: {
          SponsorList: this.stub('sponsor-list'),
        },
      },
    })
  }
}
