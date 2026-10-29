import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './MessageToast.vue'

describe('messageToast.vue', () => {
  const h = createHarness()

  const makeMessage = (overrides: Partial<ToastMessage> = {}): ToastMessage => ({
    id: '101',
    type: 'success',
    content: 'Everything is fine',
    timeout: 5,
    ...overrides,
  })

  const renderComponent = (message = makeMessage()) => {
    return h.render(Component, {
      props: { message },
    })
  }

  it('dismisses with the dismiss button', async () => {
    const { emitted } = renderComponent()
    await h.user.click(screen.getByRole('button', { name: 'Dismiss' }))

    expect(emitted().dismiss).toBeTruthy()
  })

  it('stays open when its text is clicked, so the text can be copied', async () => {
    const { emitted } = renderComponent()
    await h.user.click(screen.getByRole('main'))

    expect(emitted().dismiss).toBeUndefined()
  })

  it('dismisses upon timeout', async () => {
    vi.useFakeTimers()

    const { emitted } = renderComponent()
    vi.advanceTimersByTime(5000)
    expect(emitted().dismiss).toBeTruthy()

    vi.useRealTimers()
  })

  it('stays open while its dismiss button has keyboard focus', async () => {
    vi.useFakeTimers()

    const { emitted } = renderComponent()
    screen.getByRole('button', { name: 'Dismiss' }).focus()
    vi.advanceTimersByTime(5000)
    expect(emitted().dismiss).toBeUndefined()

    screen.getByRole('button', { name: 'Dismiss' }).blur()
    vi.advanceTimersByTime(5000)
    expect(emitted().dismiss).toBeTruthy()

    vi.useRealTimers()
  })

  it('shows a pending indicator instead of the dismiss button while loading, and never times out', () => {
    vi.useFakeTimers()

    const { emitted } = renderComponent(makeMessage({ type: 'loading' }))
    vi.advanceTimersByTime(60_000)

    screen.getByTestId('toast-pending')
    expect(screen.queryByRole('button', { name: 'Dismiss' })).toBeNull()
    expect(emitted().dismiss).toBeUndefined()

    vi.useRealTimers()
  })

  it('shows the progress in place of the pending indicator', () => {
    renderComponent(
      makeMessage({
        type: 'loading',
        progress: () => 40,
      }),
    )

    expect(screen.getByRole('progressbar').getAttribute('aria-valuenow')).toBe('40')
  })

  it('stays open while it offers actions', () => {
    vi.useFakeTimers()

    const { emitted } = renderComponent(
      makeMessage({
        actions: [
          {
            label: 'Save',
            handler: vi.fn(),
          },
        ],
      }),
    )
    vi.advanceTimersByTime(60_000)

    expect(emitted().dismiss).toBeUndefined()

    vi.useRealTimers()
  })

  it('starts timing out once it stops loading', async () => {
    vi.useFakeTimers()

    const { emitted, rerender } = renderComponent(makeMessage({ type: 'loading' }))
    await rerender({ message: makeMessage() })
    vi.advanceTimersByTime(5000)

    expect(emitted().dismiss).toBeTruthy()

    vi.useRealTimers()
  })
})
