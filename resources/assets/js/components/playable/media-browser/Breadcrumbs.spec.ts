import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './Breadcrumbs.vue'

describe('breadcrumbs.vue', () => {
  const h = createHarness()

  it('renders the library root only', () => {
    h.render(Component, { props: { current: null, ancestors: [] } })

    expect(screen.getByRole('link', { name: 'Library' }).getAttribute('href')).toBe('/#/browse')
  })

  it('renders the current folder under the root', () => {
    const current = h.factory('folder').make({ id: 'current-id', name: 'Music' })

    h.render(Component, { props: { current, ancestors: [] } })

    screen.getByRole('link', { name: 'Library' })
    screen.getByText('Music')
    expect(screen.queryByRole('link', { name: 'Music' })).toBeNull()
  })

  it('renders the full ancestor chain when within the limit', () => {
    const ancestors = [
      h.factory('folder').make({ id: 'a1', name: 'Music' }),
      h.factory('folder').make({ id: 'a2', name: 'Rock' }),
    ]
    const current = h.factory('folder').make({ id: 'cur', name: 'Pink Floyd' })

    h.render(Component, { props: { current, ancestors } })

    expect(screen.getByRole('link', { name: 'Music' }).getAttribute('href')).toBe('/#/browse/a1')
    expect(screen.getByRole('link', { name: 'Rock' }).getAttribute('href')).toBe('/#/browse/a2')
    screen.getByText('Pink Floyd')
    expect(screen.queryByText('…')).toBeNull()
  })

  it('truncates the middle when the chain is deep', () => {
    const ancestors = [
      h.factory('folder').make({ id: 'a1', name: 'Music' }),
      h.factory('folder').make({ id: 'a2', name: 'Rock' }),
      h.factory('folder').make({ id: 'a3', name: 'Progressive' }),
      h.factory('folder').make({ id: 'a4', name: 'Pink Floyd' }),
    ]
    const current = h.factory('folder').make({ id: 'cur', name: 'The Wall' })

    h.render(Component, { props: { current, ancestors } })

    screen.getByRole('link', { name: 'Library' })
    screen.getByText('…')
    expect(screen.getByRole('link', { name: 'Pink Floyd' }).getAttribute('href')).toBe('/#/browse/a4')
    screen.getByText('The Wall')

    expect(screen.queryByText('Music')).toBeNull()
    expect(screen.queryByText('Rock')).toBeNull()
    expect(screen.queryByText('Progressive')).toBeNull()
  })
})
