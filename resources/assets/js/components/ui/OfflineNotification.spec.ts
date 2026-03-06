import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './OfflineNotification.vue'

describe('OfflineNotification', () => {
  const h = createHarness()

  it('shows the offline message', () => {
    h.render(Component)
    expect(screen.getByText(/You.re offline/)).toBeTruthy()
  })

  it('dismisses when clicked', async () => {
    h.render(Component)
    await h.user.click(screen.getByTitle('Click to dismiss'))
    expect(screen.queryByText("You're offline.")).toBeNull()
  })
})
