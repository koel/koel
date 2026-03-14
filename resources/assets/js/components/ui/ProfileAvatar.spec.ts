import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ProfileAvatar.vue'

describe('profileAvatar.vue', () => {
  const h = createHarness()

  it('renders', () => {
    const user = h.factory('user', {
      name: 'John Doe',
      avatar: 'https://example.com/avatar.jpg',
    })

    expect(
      h
        .actingAsUser(user as CurrentUser)
        .render(Component)
        .html(),
    ).toMatchSnapshot()
  })
})
