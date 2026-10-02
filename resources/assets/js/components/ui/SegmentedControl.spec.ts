import { describe, expect, it, vi } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { nextTick } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SegmentedControl.vue'

describe('segmentedControl.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      Element.prototype.scrollTo = vi.fn()
    },
  })

  const options = [
    { value: 'one', label: 'One', testId: 'segment-one' },
    { value: 'two', label: 'Two', testId: 'segment-two' },
  ]

  it('checks the selected option', () => {
    h.render(Component, { props: { name: 'segments', options, modelValue: 'two' } })

    expect(screen.getByTestId('segment-one').querySelector('input')?.checked).toBe(false)
    expect(screen.getByTestId('segment-two').querySelector('input')?.checked).toBe(true)
  })

  it('shows the sliding indicator behind the selected option', async () => {
    vi.spyOn(HTMLElement.prototype, 'offsetWidth', 'get').mockReturnValue(40)
    h.render(Component, { props: { name: 'segments', options, modelValue: 'one' } })

    await screen.findByTestId('segmented-control-indicator')
  })

  it('hides the sliding indicator while the control is not displayed', async () => {
    vi.spyOn(HTMLElement.prototype, 'offsetWidth', 'get').mockReturnValue(0)
    h.render(Component, { props: { name: 'segments', options, modelValue: 'one' } })

    await nextTick()

    expect(screen.queryByTestId('segmented-control-indicator')).toBeNull()
  })

  it('scrolls the picked option into view', async () => {
    h.render(Component, { props: { name: 'segments', options, modelValue: 'one' } })

    await h.user.click(screen.getByTestId('segment-two'))

    await waitFor(() => expect(Element.prototype.scrollTo).toHaveBeenCalled())
  })
})
