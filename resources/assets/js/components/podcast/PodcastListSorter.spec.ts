import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import PodcastListSorter from './PodcastListSorter.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders and emits the proper event', async () => {
      const { emitted } = this.render(PodcastListSorter, {
        props: {
          field: 'title',
          order: 'desc',
        },
      })

      screen.getByTitle('Sorting by Title, descending')

      await this.user.click(screen.getByTitle('Sort by Title'))
      expect(emitted().sort[0]).toEqual(['title', 'asc'])

      await this.user.click(screen.getByTitle('Sort by Author'))
      expect(emitted().sort[1]).toEqual(['author', 'asc'])
    })
  }
}
