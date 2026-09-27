import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { nextTick, ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SegmentedControl.vue'

describe('segmentedControl.vue', () => {
  const h = createHarness()

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

  it('updates the model when an option is picked', async () => {
    const selected = ref('one')

    h.render(Component, {
      props: {
        name: 'segments',
        options,
        modelValue: selected.value,
        'onUpdate:modelValue': (value: string) => (selected.value = value),
      },
    })

    await h.user.click(screen.getByTestId('segment-two'))

    expect(selected.value).toBe('two')
  })
})
