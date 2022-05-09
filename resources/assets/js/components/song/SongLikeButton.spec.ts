import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { fireEvent } from '@testing-library/vue'
import { favoriteStore } from '@/stores'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import SongLikeButton from './SongLikeButton.vue'

new class extends ComponentTestCase {
  protected test () {
    it.each<[boolean, string]>([
      [true, 'btn-like-liked'],
      [false, 'btn-like-unliked']
    ])('likes or unlikes', async (liked: boolean, testId: string) => {
      const mock = this.mock(favoriteStore, 'toggleOne')
      const song = factory<Song>('song', { liked })

      const { getByTestId } = this.render(SongLikeButton, {
        props: {
          song
        }
      })

      await fireEvent.click(getByTestId(testId))

      expect(mock).toHaveBeenCalledWith(song)
    })
  }
}
