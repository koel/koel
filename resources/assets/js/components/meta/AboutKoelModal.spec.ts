import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { http } from '@/services/http'
import Component from './AboutKoelModal.vue'

describe('aboutKoelModal.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    return h.render(Component, {
      global: {
        stubs: {
          SponsorList: h.stub('sponsor-list'),
        },
      },
    })
  }

  it('renders', async () => {
    commonStore.state.current_version = 'v0.0.0'
    commonStore.state.latest_version = 'v0.0.0'

    expect(renderComponent().html()).toMatchSnapshot()
  })

  it('shows new version', () => {
    commonStore.state.current_version = 'v1.0.0'
    commonStore.state.latest_version = 'v1.0.1'
    h.actingAsAdmin()
    renderComponent().getByTestId('new-version-about')
  })

  it('shows demo notation', async () => h.withDemoMode(async () => {
    const getMock = h.mock(http, 'get').mockResolvedValue([])

    renderComponent()

    await waitFor(() => {
      screen.getByTestId('demo-credits')
      expect(getMock).toHaveBeenCalledWith('demo/credits')
    })
  }))
})
