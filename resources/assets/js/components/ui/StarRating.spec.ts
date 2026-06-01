import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './StarRating.vue'

describe('starRating.vue', () => {
  const h = createHarness()

  it('renders five radio inputs with the current rating checked', () => {
    h.render(Component, { props: { rating: 3 } })

    const stars = screen.getAllByRole('radio') as HTMLInputElement[]
    expect(stars).toHaveLength(5)
    expect(stars[2].checked).toBe(true)
    expect(stars[0].checked).toBe(false)
  })

  it('emits the chosen rating on click', async () => {
    const { emitted } = h.render(Component, { props: { rating: 0 } })

    await h.user.click(screen.getByRole('radio', { name: 'Rate 4 of 5' }))

    expect(emitted('rate')?.[0]).toEqual([4])
  })

  it('clicking the active star clears the rating', async () => {
    const { emitted } = h.render(Component, { props: { rating: 3 } })

    await h.user.click(screen.getByRole('radio', { name: 'Rate 3 of 5' }))

    expect(emitted('rate')?.[0]).toEqual([0])
  })
})
