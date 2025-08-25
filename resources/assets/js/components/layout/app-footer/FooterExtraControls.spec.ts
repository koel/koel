import { describe, expect, it } from 'vitest'
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

  it('renders', () => {
    h.setReadOnlyProperty(document, 'fullscreenEnabled', undefined)
    expect(renderComponent().html()).toMatchSnapshot()
  })

  it('toggles fullscreen mode', async () => {
    h.setReadOnlyProperty(document, 'fullscreenEnabled', true)
    renderComponent()
    const emitMock = h.mock(eventBus, 'emit')

    await h.user.click(screen.getByTitle('Enter fullscreen mode'))

    expect(emitMock).toHaveBeenCalledWith('FULLSCREEN_TOGGLE')
  })
})
