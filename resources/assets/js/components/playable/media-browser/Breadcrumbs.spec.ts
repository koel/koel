import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './Breadcrumbs.vue'

describe('breadcrumbs.vue', () => {
  const h = createHarness()

  it('renders the library root only', () => {
    const { html } = h.render(Component, {
      props: { current: null, ancestors: [] },
    })

    expect(html()).toMatchSnapshot()
  })

  it('renders the current folder under the root', () => {
    const current = h.factory('folder').make({ id: 'current-id', name: 'Music' })

    const { html } = h.render(Component, {
      props: { current, ancestors: [] },
    })

    expect(html()).toMatchSnapshot()
  })

  it('renders the full ancestor chain when within the limit', () => {
    const ancestors = [
      h.factory('folder').make({ id: 'a1', name: 'Music' }),
      h.factory('folder').make({ id: 'a2', name: 'Rock' }),
    ]
    const current = h.factory('folder').make({ id: 'cur', name: 'Pink Floyd' })

    const { html } = h.render(Component, {
      props: { current, ancestors },
    })

    expect(html()).toMatchSnapshot()
  })

  it('truncates the middle when the chain is deep', () => {
    const ancestors = [
      h.factory('folder').make({ id: 'a1', name: 'Music' }),
      h.factory('folder').make({ id: 'a2', name: 'Rock' }),
      h.factory('folder').make({ id: 'a3', name: 'Progressive' }),
      h.factory('folder').make({ id: 'a4', name: 'Pink Floyd' }),
    ]
    const current = h.factory('folder').make({ id: 'cur', name: 'The Wall' })

    const { html } = h.render(Component, {
      props: { current, ancestors },
    })

    expect(html()).toMatchSnapshot()
  })
})
