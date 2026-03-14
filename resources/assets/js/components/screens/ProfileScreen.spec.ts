import { describe, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ProfileScreen.vue'

describe('profileScreen.vue', () => {
  const h = createHarness()

  it('renders tabs', () => {
    h.render(Component)

    screen.getByText('Profile')
    screen.getByText('Preferences')
    screen.getByText('Themes')
    screen.getByText('Integrations')
    screen.getByText('Offline')
  })
})
