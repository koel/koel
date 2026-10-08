import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './FooterExtraControls.vue'

describe('footerExtraControls.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    return h.render(Component, {
      global: {
        stubs: {
          Equalizer: h.stub('Equalizer'),
          Volume: h.stub('Volume'),
        },
      },
    })
  }

  it('hides the fullscreen button when fullscreen is not supported', () => {
    h.setReadOnlyProperty(document, 'fullscreenEnabled', undefined)
    renderComponent()

    expect(screen.queryByTitle('Enter fullscreen mode')).toBeNull()
  })

  it('toggles fullscreen mode', async () => {
    h.setReadOnlyProperty(document, 'fullscreenEnabled', true)
    renderComponent()
    const emitMock = h.mock(eventBus, 'emit')

    await h.user.click(screen.getByTitle('Enter fullscreen mode'))

    expect(emitMock).toHaveBeenCalledWith('FULLSCREEN_TOGGLE')
  })
})
