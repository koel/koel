import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import ArtistListSorter from './ArtistListSorter.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders and emits the proper event', async () => {
      const { emitted } = this.render(ArtistListSorter, {
        props: {
          field: 'name',
          order: 'asc',
        },
      })

      screen.getByTitle('Sorting by Name, ascending')

      await this.user.click(screen.getByTitle('Sort by Name'))
      expect(emitted().sort[0]).toEqual(['name', 'desc'])

      await this.user.click(screen.getByTitle('Sort by Date Added'))
      expect(emitted().sort[1]).toEqual(['created_at', 'asc'])
    })
  }
}
