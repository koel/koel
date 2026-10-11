import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './FavoritesOnlyToggle.vue'

describe('favoritesOnlyToggle.vue', () => {
  const h = createHarness()

  it.each([true, false])('reflects active=%s as its pressed state', active => {
    h.render(Component, { props: { active } })

    expect(screen.getByRole('button', { name: 'Favorites only' }).getAttribute('aria-pressed')).toBe(String(active))
  })

  it('asks to toggle when clicked', async () => {
    const { emitted } = h.render(Component, { props: { active: false } })

    await h.user.click(screen.getByRole('button', { name: 'Favorites only' }))

    expect(emitted().toggle).toHaveLength(1)
  })
})
