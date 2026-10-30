import { describe, expect, it } from 'vite-plus/test'
import { defineComponent, h as createVNode } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import Component from './OfflineNotification.vue'

describe('offlineNotification', () => {
  const h = createHarness()

  const Host = defineComponent({
    props: { offline: Boolean },
    setup: props => () => createVNode('div', null, props.offline ? [createVNode(Component)] : []),
  })

  it('shows a lasting warning while offline and dismisses it once back online', async () => {
    const warningMock = h.mock(MessageToasterStub.value, 'warning', 'toast-id')
    const dismissMock = h.mock(MessageToasterStub.value, 'dismiss')

    const { rerender } = h.render(Host, { props: { offline: true } })

    expect(warningMock).toHaveBeenCalledWith(expect.objectContaining({ persistent: true }))

    await rerender({ offline: false })

    expect(dismissMock).toHaveBeenCalledWith('toast-id')
  })
})
