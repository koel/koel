import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './MessageToast.vue'

describe('messageToast.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    return h.render(Component, {
      props: {
        message: {
          id: 101,
          type: 'success',
          message: 'Everything is fine',
          timeout: 5,
        },
      },
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
})
