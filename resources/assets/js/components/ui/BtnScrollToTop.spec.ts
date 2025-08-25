import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { $ } from '@/utils/$'

import Component from './BtnScrollToTop.vue'

describe('btnScrollToTop.vue', () => {
  const h = createHarness()

  it('renders', () => expect(h.render(Component).html()).toMatchSnapshot())

  it('scrolls to top', async () => {
    const mock = h.mock($, 'scrollTo')
    h.render(Component)

    await h.user.click(screen.getByTitle('Scroll to top'))

    expect(mock).toHaveBeenCalled()
  })
})
