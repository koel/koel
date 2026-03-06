import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './UserAvatar.vue'

describe('UserAvatar', () => {
  const h = createHarness()

  it('renders avatar image with alt text', () => {
    const user = h.factory('user', { name: 'Alice', avatar: 'https://example.com/avatar.png' })
    const { container } = h.render(Component, { props: { user } })
    const img = container.querySelector('img')!
    expect(img.alt).toBe('Avatar of Alice')
    expect(img.src).toBe('https://example.com/avatar.png')
  })
})
