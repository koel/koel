import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ThumbnailStack.vue'

describe('thumbnailStack.vue', () => {
  const h = createHarness()

  it('displays 4 thumbnails at most', () => {
    const { getAllByTestId } = h.render(Component, {
      props: {
        thumbnails: [
          'https://via.placeholder.com/150',
          'https://via.placeholder.com/150?foo',
          'https://via.placeholder.com/150?bar',
          'https://via.placeholder.com/150?baz',
          'https://via.placeholder.com/150?qux',
        ],
      },
    })

    expect(getAllByTestId('thumbnail')).toHaveLength(4)
  })

  it('displays the first thumbnail if less than 4 are provided', () => {
    const { getAllByTestId } = h.render(Component, {
      props: {
        thumbnails: [
          'https://via.placeholder.com/150',
          'https://via.placeholder.com/150?foo',
        ],
      },
    })

    expect(getAllByTestId('thumbnail')).toHaveLength(1)
  })
})
