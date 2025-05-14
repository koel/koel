import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import AlbumListSorter from './AlbumListSorter.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders and emits the proper event', async () => {
      const { emitted } = this.render(AlbumListSorter, {
        props: {
          field: 'name',
          order: 'asc',
        },
      })

      screen.getByTitle('Sorting by Name, ascending')

      await this.user.click(screen.getByTitle('Sort by Name'))
      expect(emitted().sort[0]).toEqual(['name', 'desc'])

      await this.user.click(screen.getByTitle('Sort by Release Year'))
      expect(emitted().sort[1]).toEqual(['year', 'asc'])

      await this.user.click(screen.getByTitle('Sort by Artist'))
      expect(emitted().sort[2]).toEqual(['artist_name', 'asc'])

      await this.user.click(screen.getByTitle('Sort by Date Added'))
      expect(emitted().sort[3]).toEqual(['created_at', 'asc'])
    })
  }
}
