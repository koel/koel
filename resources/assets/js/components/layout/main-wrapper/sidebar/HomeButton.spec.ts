import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './HomeButton.vue'

describe('homeButton.vue', () => {
  const h = createHarness()

  it('triggers the sidebar toggle event', async () => {
    h.mock(eventBus, 'emit')
    h.render(Component)
    await h.user.click(screen.getByRole('link'))

    expect(eventBus.emit).toHaveBeenCalledWith('TOGGLE_SIDEBAR')
  })
})
