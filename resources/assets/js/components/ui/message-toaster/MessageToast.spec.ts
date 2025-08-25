import { describe, expect, it, vi } from 'vitest'
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

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())

  it('dismisses upon click', async () => {
    const { emitted } = renderComponent()
    await h.user.click(screen.getByTitle('Click to dismiss'))

    expect(emitted().dismiss).toBeTruthy()
  })

  it('dismisses upon timeout', async () => {
    vi.useFakeTimers()

    const { emitted } = renderComponent()
    vi.advanceTimersByTime(5000)
    expect(emitted().dismiss).toBeTruthy()

    vi.useRealTimers()
  })
})
