import { describe, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './EditableProfileAvatar.vue'

describe('editableProfileAvatar.vue', () => {
  const h = createHarness()

  it('renders avatar controls', () => {
    h.render(Component, {
      props: {
        profile: { name: 'John Doe', avatar: 'https://example.com/avatar.png' },
      },
    })

    screen.getByTitle('Pick a new avatar')
    screen.getByTitle('Reset avatar')
  })
})
