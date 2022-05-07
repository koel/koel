import { beforeEach, expect, it } from 'vitest'
import { cleanup } from '@testing-library/vue'
import { mockHelper, render } from '@/__tests__/__helpers__'
import { commonStore, userStore } from '@/stores'
import AboutKoelModel from './AboutKoelModal.vue'
import Btn from '@/components/ui/Btn.vue'
import factory from '@/__tests__/factory'

beforeEach(() => {
  cleanup()
  mockHelper.restoreAllMocks()
  KOEL_ENV = ''
})

it('renders', async () => {
  commonStore.state.currentVersion = 'v0.0.0'
  commonStore.state.latestVersion = 'v0.0.0'

  const { html } = render(AboutKoelModel, {
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
  const { findByTestId } = render(AboutKoelModel)

  findByTestId('new-version-about')
})

it('shows demo notation', () => {
  KOEL_ENV = 'demo'
  const { findByTestId } = render(AboutKoelModel)

  findByTestId('demo-credits')
})
