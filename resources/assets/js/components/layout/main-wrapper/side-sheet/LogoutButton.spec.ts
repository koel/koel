import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './LogoutButton.vue'

describe('logoutButton.vue', () => {
  const h = createHarness()

  it('emits the logout event', async () => {
    const emitMock = h.mock(eventBus, 'emit')
    h.render(Component)

    await h.user.click(screen.getByRole('button'))

    expect(emitMock).toHaveBeenCalledWith('LOG_OUT')
  })
})
