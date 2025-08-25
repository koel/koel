import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './Breadcrumbs.vue'

describe('breadcrumbs.vue', () => {
  const h = createHarness()

  it.each([[''], ['/var'], ['/var/media/'], ['/var/media/deep/nested/path']])('renders', path => {
    const { html } = h.render(Component, {
      props: {
        path,
      },
    })

    expect(html()).toMatchSnapshot()
  })
})
