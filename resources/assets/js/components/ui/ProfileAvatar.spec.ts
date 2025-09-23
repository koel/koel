import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ProfileAvatar.vue'

describe('profileAvatar.vue', () => {
  const h = createHarness()

  it('renders', () => {
    const user = h.factory('user', {
      name: 'John Doe',
      avatar: 'https://example.com/avatar.jpg',
    })

    expect(h.actingAsUser(user).render(Component).html()).toMatchSnapshot()
  })
})
