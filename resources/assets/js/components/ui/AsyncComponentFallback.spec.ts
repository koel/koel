import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import type { LoadFailureCause } from '@/utils/loadFailure'

const detectLoadFailureCauseMock = vi.fn<() => Promise<LoadFailureCause>>()

vi.mock('@/utils/loadFailure', () => ({
  detectLoadFailureCause: () => detectLoadFailureCauseMock(),
}))

import Component from './AsyncComponentFallback.vue'

describe('asyncComponentFallback', () => {
  const h = createHarness()

  it('says the user is offline', async () => {
    detectLoadFailureCauseMock.mockResolvedValue('offline')
    h.render(Component)

    await screen.findByText("You're offline.")
    expect(screen.queryByRole('button', { name: 'Reload' })).toBeNull()
  })

  it('offers a reload when Koel has been updated', async () => {
    detectLoadFailureCauseMock.mockResolvedValue('outdated')
    h.render(Component)

    await screen.findByText('Koel has been updated.')
    screen.getByRole('button', { name: 'Reload' })
  })

  it('offers a reload when the cause is unknown', async () => {
    detectLoadFailureCauseMock.mockResolvedValue('unknown')
    h.render(Component)

    await screen.findByText("Couldn't load this part of Koel.")
    screen.getByRole('button', { name: 'Reload' })
    expect(screen.queryByText("You're offline.")).toBeNull()
  })
})
