import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AlbumOrArtistInfo.vue'

describe('albumOrArtistInfo.vue', () => {
  const h = createHarness()

  it('renders header in aside mode', () => {
    const { getByText } = h.render(Component, {
      props: { mode: 'aside' },
      slots: {
        header: 'Artist Name',
        default: 'Bio content',
      },
    })

    getByText('Artist Name')
    getByText('Bio content')
  })

  it('does not render header in full mode', () => {
    const { queryByText, getByText } = h.render(Component, {
      props: { mode: 'full' },
      slots: {
        header: 'Artist Name',
        default: 'Bio content',
      },
    })

    expect(queryByText('Artist Name')).toBeNull()
    getByText('Bio content')
  })

  it('renders footer slot when provided', () => {
    const { getByText } = h.render(Component, {
      props: { mode: 'aside' },
      slots: {
        default: 'Content',
        footer: 'Footer content',
      },
    })

    getByText('Footer content')
  })
})
