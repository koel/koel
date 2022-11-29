import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { screen } from '@testing-library/vue'
import { favoriteStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SongLikeButton from './SongLikeButton.vue'

new class extends UnitTestCase {
  protected test () {
    it.each<[string, boolean]>([
      ['Unlike Foo by Bar', true],
      ['Like Foo by Bar', false]
    ])('%s', async (name: string, liked: boolean) => {
      const mock = this.mock(favoriteStore, 'toggleOne')
      const song = factory<Song>('song', {
        liked,
        title: 'Foo',
        artist_name: 'Bar'
      })

      this.render(SongLikeButton, {
        props: {
          song
        }
      })

      await this.user.click(screen.getByRole('button', { name }))

      expect(mock).toHaveBeenCalledWith(song)
    })
  }
}
