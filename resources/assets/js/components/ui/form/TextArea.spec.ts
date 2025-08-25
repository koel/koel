import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './TextArea.vue'

describe('textArea.vue', () => {
  const h = createHarness()

  it('emits value', async () => {
    const { emitted } = h.render(Component)

    await h.type(screen.getByRole('textbox'), 'Hi')

    expect(emitted()['update:modelValue']).toStrictEqual([
      ['H'],
      ['Hi'],
    ])
  })
})
