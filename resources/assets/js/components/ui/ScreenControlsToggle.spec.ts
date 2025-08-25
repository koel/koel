import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ScreenControlsToggle.vue'

describe('screenControlsToggle.vue', () => {
  const h = createHarness()

  it('renders and emits an event', async () => {
    const { emitted } = h.render(Component)
    await h.user.click(screen.getByRole('checkbox'))
    expect(emitted()['update:modelValue']).toBeTruthy()
  })
})
