import { expect, it } from 'vitest'
import { commonStore, userStore } from '@/stores'
import factory from '@/__tests__/factory'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import AboutKoelModel from './AboutKoelModal.vue'
import Btn from '@/components/ui/Btn.vue'

new class extends ComponentTestCase {
  protected beforeEach () {
    super.beforeEach(() => (KOEL_ENV = ''));
  }

  protected test () {
    it('renders', async () => {
      commonStore.state.currentVersion = 'v0.0.0'
      commonStore.state.latestVersion = 'v0.0.0'

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
      commonStore.state.currentVersion = 'v1.0.0'
      commonStore.state.latestVersion = 'v1.0.1'
      userStore.state.current = factory.states('admin')<User>('user')
      const { findByTestId } = this.render(AboutKoelModel)

      findByTestId('new-version-about')
    })

    it('shows demo notation', () => {
      KOEL_ENV = 'demo'
      const { findByTestId } = this.render(AboutKoelModel)

      findByTestId('demo-credits')
    })
  }
}
