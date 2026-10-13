import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ShelfRefreshButton.vue'

const handleHttpErrorMock = vi.fn()

vi.mock('@/composables/useErrorHandler', () => ({
  useErrorHandler: () => ({ handleHttpError: handleHttpErrorMock }),
}))

describe('shelfRefreshButton.vue', () => {
  const h = createHarness()

  it('refreshes the shelf on click', async () => {
    const refresh = vi.fn().mockResolvedValue([])
    h.render(Component, { props: { refresh } })

    await h.user.click(screen.getByRole('button', { name: 'Refresh' }))

    expect(refresh).toHaveBeenCalled()
  })

  it('handles a failed refresh', async () => {
    const error = new Error('Network down')
    const refresh = vi.fn().mockRejectedValue(error)
    h.render(Component, { props: { refresh } })

    await h.user.click(screen.getByRole('button', { name: 'Refresh' }))

    await waitFor(() => expect(handleHttpErrorMock).toHaveBeenCalledWith(error))
    expect(screen.getByRole<HTMLButtonElement>('button', { name: 'Refresh' }).disabled).toBe(false)
  })
})
