import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ScreenBase.vue'

describe('screenBase', () => {
  const h = createHarness()

  it('renders header and default slots', () => {
    h.render(Component, {
      slots: {
        header: 'Screen Header',
        default: 'Screen Content',
      },
    })

    screen.getByText('Screen Header')
    screen.getByText('Screen Content')
  })

  it('renders cover background when backgroundImage is provided', () => {
    h.render(Component, {
      props: {
        backgroundImage: 'https://example.com/cover.jpg',
      },
      slots: {
        default: 'Content',
      },
    })

    const bg = screen.getByTestId('cover-bg')
    expect(bg.style.backgroundImage).toContain('https://example.com/cover.jpg')
  })

  it('does not render cover background when backgroundImage is not provided', () => {
    h.render(Component, {
      slots: {
        default: 'Content',
      },
    })

    expect(screen.queryByTestId('cover-bg')).toBeNull()
  })
})
