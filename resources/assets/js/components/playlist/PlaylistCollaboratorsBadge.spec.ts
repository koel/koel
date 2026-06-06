import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PlaylistCollaboratorsBadge.vue'

describe('PlaylistCollaboratorsBadge', () => {
  const h = createHarness()

  it('displays up to 3 collaborators', () => {
    const collaborators = [h.factory('user').make(), h.factory('user').make(), h.factory('user').make()]
    const { container } = h.render(Component, { props: { collaborators } })
    expect(container.querySelectorAll('li')).toHaveLength(3)
  })

  it('shows remainder count when more than 3', () => {
    const collaborators = Array.from({ length: 5 }, () => h.factory('user').make())
    h.render(Component, { props: { collaborators } })
    screen.getByText('+2 more')
  })

  it('does not show remainder for 3 or fewer', () => {
    const collaborators = [h.factory('user').make(), h.factory('user').make()]
    h.render(Component, { props: { collaborators } })
    expect(screen.queryByText(/more/)).toBeNull()
  })
})
