import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ScreenHeader.vue'

describe('screenHeader', () => {
  const h = createHarness()

  it('renders the heading from the default slot', () => {
    h.render(Component, {
      slots: {
        default: 'My Album Title',
      },
    })

    expect(screen.getByRole('heading', { level: 1 }).textContent).toContain('My Album Title')
  })

  it('renders the thumbnail slot', () => {
    h.render(Component, {
      slots: {
        default: 'Title',
        thumbnail: '<img src="/thumb.jpg" alt="thumb" />',
      },
    })

    screen.getByAltText('thumb')
  })

  it('renders meta slot when provided', () => {
    h.render(Component, {
      slots: {
        default: 'Title',
        meta: '<span>3 songs</span>',
      },
    })

    screen.getByText('3 songs')
  })

  it('renders controls slot', () => {
    h.render(Component, {
      slots: {
        default: 'Title',
        controls: '<button>Play</button>',
      },
    })

    screen.getByRole('button', { name: 'Play' })
  })

  it('applies the disabled state', () => {
    const { container } = h.render(Component, {
      props: { disabled: true },
      slots: { default: 'Title' },
    })

    expect(container.querySelector('header')?.classList.contains('disabled')).toBe(true)
  })

  it('applies the layout prop', () => {
    const { container } = h.render(Component, {
      props: { layout: 'collapsed' },
      slots: { default: 'Title' },
    })

    expect(container.querySelector('header')?.classList.contains('collapsed')).toBe(true)
  })

  it('defaults to expanded layout', () => {
    const { container } = h.render(Component, {
      slots: { default: 'Title' },
    })

    expect(container.querySelector('header')?.classList.contains('expanded')).toBe(true)
  })
})
