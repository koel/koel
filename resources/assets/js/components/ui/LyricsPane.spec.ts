import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils'
import { fireEvent } from '@testing-library/vue'
import LyricsPane from './LyricsPane.vue'
import TextMagnifier from '@/components/ui/TextMagnifier.vue'

new class extends UnitTestCase {
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
          TextMagnifier
        }
      }
    })
  }

  protected test () {
    it('renders', () => {
      expect(this.renderComponent().html()).toMatchSnapshot()
    })

    it('provides a button to add lyrics if current user is admin', async () => {
      const song = factory<Song>('song', {
        lyrics: null
      })

      const mock = this.mock(eventBus, 'emit')
      const { getByTestId } = this.actingAsAdmin().renderComponent(song)

      await fireEvent.click(getByTestId('add-lyrics-btn'))

      expect(mock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_SONG_FORM', song, 'lyrics')
    })

    it('does not have a button to add lyrics if current user is not an admin', async () => {
      const { queryByTestId } = this.actingAs().renderComponent(factory<Song>('song', {
        lyrics: null
      }))

      expect(await queryByTestId('add-lyrics-btn')).toBeNull()
    })
  }
}
