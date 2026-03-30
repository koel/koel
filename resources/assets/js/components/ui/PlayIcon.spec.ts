import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PlayIcon.vue'

describe('playIcon.vue', () => {
  const h = createHarness()

  it('renders play state by default', () => {
    expect(h.render(Component).html()).toMatchSnapshot()
  })

  it('renders pause state when playing', () => {
    expect(h.render(Component, { props: { playing: true } }).html()).toMatchSnapshot()
  })
})
