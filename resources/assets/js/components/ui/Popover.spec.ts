import { describe, expect, it } from 'vite-plus/test'
import { defineComponent, h, nextTick, ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import Popover from './Popover.vue'

const mount = (popoverProps: Record<string, unknown> = {}) => {
  const harness = createHarness()
  const events: boolean[] = []
  const Host = defineComponent({
    setup() {
      const trigger = ref<HTMLButtonElement>()
      const popover = ref<InstanceType<typeof Popover>>()
      return () =>
        h('div', [
          h('button', { ref: trigger, type: 'button' }, 'Open'),
          h(
            Popover,
            {
              ref: popover,
              anchor: trigger.value,
              onToggle: (open: boolean) => events.push(open),
              ...popoverProps,
            },
            () => 'panel content',
          ),
        ])
    },
  })
  const rendered = harness.render(Host)
  return {
    container: rendered.container,
    events,
    button: () => rendered.container.querySelector<HTMLButtonElement>('button')!,
    panel: () => rendered.container.querySelector<HTMLElement>('[popover]')!,
  }
}

describe('popover.vue', () => {
  it('renders a popover panel with the requested mode', async () => {
    const { panel } = mount({ mode: 'auto' })
    await nextTick()
    expect(panel().getAttribute('popover')).toBe('auto')
    expect(panel().textContent).toContain('panel content')
  })

  it('defaults to auto mode', async () => {
    const { panel } = mount()
    await nextTick()
    expect(panel().getAttribute('popover')).toBe('auto')
  })

  it('supports manual mode', async () => {
    const { panel } = mount({ mode: 'manual' })
    await nextTick()
    expect(panel().getAttribute('popover')).toBe('manual')
  })

  it('wires aria attributes onto the anchor', async () => {
    const { button } = mount()
    await nextTick()
    expect(button().getAttribute('aria-haspopup')).toBe('menu')
    expect(button().getAttribute('aria-expanded')).toBe('false')
    expect(button().getAttribute('aria-controls')).toMatch(/^popover-/)
  })

  it('updates aria-expanded when toggled', async () => {
    const { button, panel } = mount()
    await nextTick()

    panel().showPopover()
    await nextTick()
    expect(button().getAttribute('aria-expanded')).toBe('true')

    panel().hidePopover()
    await nextTick()
    expect(button().getAttribute('aria-expanded')).toBe('false')
  })

  it('emits toggle events with the new open state', async () => {
    const { events, panel } = mount()
    await nextTick()

    panel().showPopover()
    panel().hidePopover()
    await nextTick()
    expect(events).toEqual([true, false])
  })
})
