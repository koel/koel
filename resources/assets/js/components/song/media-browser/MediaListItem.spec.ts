import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { screen } from '@testing-library/vue'
import MediaListItem from './MediaListItem.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders a song', async () => {
      const item = factory('song', {
        basename: 'whatever.mp3',
      })

      const { emitted } = this.render(MediaListItem, {
        props: {
          item,
        },
      })

      await this.user.click(screen.getByTitle('Play'))

      expect(emitted()['play-song']).toBeTruthy()
    })

    it('renders a folder', async () => {
      const item = factory('folder')

      const { emitted } = this.render(MediaListItem, {
        props: {
          item,
        },
      })

      await this.user.click(screen.getByTitle('Open'))

      expect(emitted()['open-folder']).toBeTruthy()
    })
  }
}
