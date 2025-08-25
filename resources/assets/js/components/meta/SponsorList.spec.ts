import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SponsorList.vue'

describe('sponsorList.vue', () => {
  const h = createHarness()

  it('renders', () => {
    expect(h.render(Component).html()).toMatchSnapshot()
  })
})
