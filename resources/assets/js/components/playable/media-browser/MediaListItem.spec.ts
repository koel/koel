import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { screen } from '@testing-library/vue'
import Component from './MediaListItem.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders a playable', async () => {
      const item = factory('song', {
        basename: 'whatever.mp3',
      })

      const { emitted } = this.render(Component, {
        props: {
          item,
        },
      })

      await this.user.click(screen.getByTitle('Play'))

      expect(emitted()['play-song']).toBeTruthy()
    })

    it('renders a folder', async () => {
      const item = factory('folder')

      const { emitted } = this.render(Component, {
        props: {
          item,
        },
      })

      await this.user.click(screen.getByTitle('Open'))

      expect(emitted()['open-folder']).toBeTruthy()
    })
  }
}
