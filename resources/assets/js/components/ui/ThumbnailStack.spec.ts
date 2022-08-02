import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import ThumbnailStack from './ThumbnailStack.vue'

new class extends UnitTestCase {
  protected test () {
    it('displays 4 thumbnails at most', () => {
      const { getAllByTestId } = this.render(ThumbnailStack, {
        props: {
          thumbnails: [
            'https://via.placeholder.com/150',
            'https://via.placeholder.com/150?foo',
            'https://via.placeholder.com/150?bar',
            'https://via.placeholder.com/150?baz',
            'https://via.placeholder.com/150?qux'
          ]
        }
      })

      expect(getAllByTestId('thumbnail')).toHaveLength(4)
    })

    it('displays the first thumbnail if less than 4 are provided', () => {
      const { getAllByTestId } = this.render(ThumbnailStack, {
        props: {
          thumbnails: [
            'https://via.placeholder.com/150',
            'https://via.placeholder.com/150?foo'
          ]
        }
      })

      expect(getAllByTestId('thumbnail')).toHaveLength(1)
    })
  }
}
