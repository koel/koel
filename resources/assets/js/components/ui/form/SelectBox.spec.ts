import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SelectBox.vue'

describe('selectBox.vue', () => {
  const h = createHarness()

  it('renders select with slot options', () => {
    const { getByRole } = h.render(Component, {
      slots: { default: '<option value="a">A</option><option value="b">B</option>' },
    })

    expect(getByRole('combobox')).not.toBeNull()
  })

  it('updates model value on change', async () => {
    const { getByRole, emitted } = h.render(Component, {
      props: { modelValue: 'a' },
      slots: { default: '<option value="a">A</option><option value="b">B</option>' },
    })

    await h.user.selectOptions(getByRole('combobox'), 'b')
    expect(emitted()['update:modelValue'][0]).toEqual(['b'])
  })
})
