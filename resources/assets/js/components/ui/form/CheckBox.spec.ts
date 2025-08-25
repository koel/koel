import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './CheckBox.vue'

describe('checkBox.vue', () => {
  const h = createHarness()

  it('renders unchecked state', () => expect(h.render(Component).html()).toMatchSnapshot())

  it('renders checked state', () => expect(h.render(Component, {
    props: {
      modelValue: true,
    },
  }).html()).toMatchSnapshot())

  it('emits the input event', async () => {
    const { emitted } = h.render(Component)

    await h.trigger(screen.getByRole('checkbox'), 'click')

    expect(emitted()['update:modelValue']).toBeTruthy()
  })
})
