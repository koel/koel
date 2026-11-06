import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ExpandableContentBlock.vue'

describe('expandableContentBlock.vue', () => {
  const h = createHarness()

  it('hides the More button after clicking it', async () => {
    h.render(Component, {
      slots: { default: 'Long content here' },
    })

    await h.user.click(screen.getByRole('button', { name: 'More' }))
    expect(screen.queryByRole('button', { name: 'More' })).toBeNull()
  })
})
