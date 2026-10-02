import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './CheckBox.vue'

describe('checkBox.vue', () => {
  const h = createHarness()

  it('renders unchecked state', () => expect(h.render(Component).html()).toMatchSnapshot())

  it('renders checked state', () =>
    expect(
      h
        .render(Component, {
          props: {
            modelValue: true,
          },
        })
        .html(),
    ).toMatchSnapshot())
})
