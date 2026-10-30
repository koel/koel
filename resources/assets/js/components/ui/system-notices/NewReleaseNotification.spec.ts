import { describe, expect, it } from 'vite-plus/test'
import { nextTick } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { commonStore } from '@/stores/commonStore'
import Component from './NewReleaseNotification.vue'

describe('newReleaseNotification', () => {
  const h = createHarness({
    beforeEach: () => {
      localStorage.clear()
      commonStore.state.current_version = 'v1.0.0'
      commonStore.state.latest_version = 'v1.1.0'
    },
  })

  it('stays dismissed for the dismissed version', () => {
    const infoMock = h.mock(MessageToasterStub.value, 'info')
    h.actingAsAdmin().render(Component)

    infoMock.mock.calls[0][0].onDismiss()
    h.render(Component)

    expect(infoMock).toHaveBeenCalledOnce()
  })

  it('shows again for a newer version than the dismissed one', async () => {
    const infoMock = h.mock(MessageToasterStub.value, 'info')
    h.actingAsAdmin().render(Component)
    infoMock.mock.calls[0][0].onDismiss()
    await nextTick()

    commonStore.state.latest_version = 'v1.2.0'
    await nextTick()

    expect(infoMock).toHaveBeenCalledTimes(2)
    expect(infoMock.mock.calls[1][0].props.releaseUrl).toBe('https://github.com/koel/koel/releases/tag/v1.2.0')
  })
})
