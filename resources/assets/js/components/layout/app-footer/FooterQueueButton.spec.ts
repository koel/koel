import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Router from '@/router'
import Component from './FooterQueueButton.vue'

describe('footerQueueButton.vue', () => {
  const h = createHarness()

  it('goes to queue screen', async () => {
    const goMock = h.mock(Router, 'go')
    h.render(Component)

    await h.user.click(screen.getByRole('button'))
    expect(goMock).toHaveBeenCalledWith('/#/queue')
  })

  it('goes back if current screen is Queue', async () => {
    h.router.$currentRoute.value = {
      screen: 'Queue',
      path: '/queue',
    }

    const goMock = h.mock(Router, 'go')
    h.render(Component)

    await h.user.click(screen.getByRole('button'))
    expect(goMock).toHaveBeenCalledWith(-1)
  })
})
