import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils'
import { screen } from '@testing-library/vue'
import LyricsPane from './LyricsPane.vue'
import Magnifier from '@/components/ui/Magnifier.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('provides a button to add lyrics if current user is admin', async () => {
      const song = factory<Song>('song', { lyrics: null })

      const mock = this.mock(eventBus, 'emit')
      this.beAdmin().renderComponent(song)

      await this.user.click(screen.getByRole('button', { name: 'Click here' }))

      expect(mock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_SONG_FORM', song, 'lyrics')
    })

    it('does not have a button to add lyrics if current user is not an admin', async () => {
      this.be().renderComponent(factory<Song>('song', { lyrics: null }))
      expect(screen.queryByRole('button', { name: 'Click here' })).toBeNull()
    })
  }

  private renderComponent (song?: Song) {
    song = song || factory<Song>('song', {
      lyrics: 'Foo bar baz qux'
    })

    return this.render(LyricsPane, {
      props: {
        song
      },
      global: {
        stubs: {
          Magnifier
        }
      }
    })
  }
}
