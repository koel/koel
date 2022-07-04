import { expect, it } from 'vitest'
import { commonStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import AboutKoelModel from './AboutKoelModal.vue'
import Btn from '@/components/ui/Btn.vue'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => (VITE_KOEL_ENV = ''))
  }

  protected test () {
    it('renders', async () => {
      commonStore.state.current_version = 'v0.0.0'
      commonStore.state.latest_version = 'v0.0.0'

      const { html } = this.render(AboutKoelModel, {
        global: {
          stubs: {
            Btn
          }
        }
      })

      expect(html()).toMatchSnapshot()
    })

    it('shows new version', () => {
      commonStore.state.current_version = 'v1.0.0'
      commonStore.state.latest_version = 'v1.0.1'
      const { findByTestId } = this.actingAsAdmin().render(AboutKoelModel)

      findByTestId('new-version-about')
    })

    it('shows demo notation', () => {
      VITE_KOEL_ENV = 'demo'
      const { findByTestId } = this.render(AboutKoelModel)

      findByTestId('demo-credits')
    })
  }
}
