import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ScreenBase.vue'

describe('screenBase', () => {
  const h = createHarness()

  it('renders header and default slots', () => {
    const { getByText } = h.render(Component, {
      slots: {
        header: 'Screen Header',
        default: 'Screen Content',
      },
    })

    getByText('Screen Header')
    getByText('Screen Content')
  })

  it('renders cover background when backgroundImage is provided', () => {
    const { container } = h.render(Component, {
      props: {
        backgroundImage: 'https://example.com/cover.jpg',
      },
      slots: {
        default: 'Content',
      },
    })

    const bg = container.querySelector('.cover-bg') as HTMLElement
    expect(bg).toBeTruthy()
    expect(bg.style.backgroundImage).toContain('https://example.com/cover.jpg')
  })

  it('does not render cover background when backgroundImage is not provided', () => {
    const { container } = h.render(Component, {
      slots: {
        default: 'Content',
      },
    })

    expect(container.querySelector('.cover-bg')).toBeNull()
  })
})
