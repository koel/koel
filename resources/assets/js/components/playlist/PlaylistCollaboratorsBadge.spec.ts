import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PlaylistCollaboratorsBadge.vue'

describe('PlaylistCollaboratorsBadge', () => {
  const h = createHarness()

  it('displays up to 3 collaborators', () => {
    const collaborators = [h.factory('user'), h.factory('user'), h.factory('user')]
    const { container } = h.render(Component, { props: { collaborators } })
    expect(container.querySelectorAll('li')).toHaveLength(3)
  })

  it('shows remainder count when more than 3', () => {
    const collaborators = Array.from({ length: 5 }, () => h.factory('user'))
    h.render(Component, { props: { collaborators } })
    expect(screen.getByText('+2 more')).toBeTruthy()
  })

  it('does not show remainder for 3 or fewer', () => {
    const collaborators = [h.factory('user'), h.factory('user')]
    h.render(Component, { props: { collaborators } })
    expect(screen.queryByText(/more/)).toBeNull()
  })
})
