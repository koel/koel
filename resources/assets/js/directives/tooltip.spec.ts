import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { defineComponent, ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { tooltip } from './tooltip'

describe('tooltip directive', () => {
  const h = createHarness()

  const label = ref('')

  const renderButton = (text = 'More actions') => {
    label.value = text

    return h.render(
      defineComponent({
        directives: { koelTooltip: tooltip },
        setup: () => ({ label }),
        template: '<button v-koel-tooltip="label">Click</button>',
      }),
    )
  }

  const tooltipElement = () => document.body.querySelector<HTMLElement>('.tooltip')

  it('does not create the tooltip on mount', () => {
    renderButton()

    expect(tooltipElement()).toBeNull()
  })

  it('creates the tooltip on hover', async () => {
    renderButton()

    await h.user.hover(screen.getByRole('button'))

    expect(tooltipElement()!.classList.contains('show')).toBe(true)
    expect(tooltipElement()!.querySelector('.tooltip-content')!.textContent).toBe('More actions')
  })

  it('creates the tooltip on keyboard focus', async () => {
    renderButton()

    await h.user.tab()

    expect(tooltipElement()!.classList.contains('show')).toBe(true)
  })

  it('shows text updated before the first show', async () => {
    renderButton()

    label.value = 'Fewer actions'
    await h.tick()
    await h.user.hover(screen.getByRole('button'))

    expect(tooltipElement()!.querySelector('.tooltip-content')!.textContent).toBe('Fewer actions')
  })

  it('removes the tooltip on unmount', async () => {
    const { unmount } = renderButton()

    await h.user.hover(screen.getByRole('button'))
    unmount()

    expect(tooltipElement()).toBeNull()
  })
})
