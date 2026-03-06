import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PlaylistThumbnail.vue'

describe('PlaylistThumbnail', () => {
  const h = createHarness()

  it('renders the thumbnail article', () => {
    const playlist = h.factory('playlist')
    const { getByTestId } = h.render(Component, { props: { playlist } })
    expect(getByTestId('playlist-thumbnail')).toBeTruthy()
  })

  it('renders slot content', () => {
    const playlist = h.factory('playlist')
    const { getByTestId } = h.render(Component, {
      props: { playlist },
      slots: { default: '<span>Overlay</span>' },
    })
    expect(getByTestId('playlist-thumbnail').querySelector('span')).toBeTruthy()
  })
})
