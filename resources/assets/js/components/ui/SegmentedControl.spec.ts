import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { ref } from 'vue'
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
