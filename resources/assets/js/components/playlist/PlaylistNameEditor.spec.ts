import factory from '@/__tests__/factory'
import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import { playlistStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import PlaylistNameEditor from './PlaylistNameEditor.vue'

let playlist: Playlist

new class extends UnitTestCase {
  private renderComponent () {
    playlist = factory<Playlist>('playlist', {
      id: 99,
      name: 'Foo'
    })

    return this.render(PlaylistNameEditor, {
      props: {
        playlist
      }
    }).getByRole('textbox')
  }

  protected test () {
    it('updates a playlist name on blur', async () => {
      const updateMock = this.mock(playlistStore, 'update')
      const input = this.renderComponent()

      await fireEvent.update(input, 'Bar')
      await fireEvent.blur(input)

      expect(updateMock).toHaveBeenCalledWith(playlist, { name: 'Bar' })
    })

    it('updates a playlist name on enter', async () => {
      const updateMock = this.mock(playlistStore, 'update')
      const input = this.renderComponent()

      await fireEvent.update(input, 'Bar')
      await fireEvent.keyUp(input, { key: 'Enter' })

      expect(updateMock).toHaveBeenCalledWith(playlist, { name: 'Bar' })
    })

    it('cancels updating on esc', async () => {
      const updateMock = this.mock(playlistStore, 'update')
      const input = this.renderComponent()

      await fireEvent.update(input, 'Bar')
      await fireEvent.keyUp(input, { key: 'Esc' })

      expect(input.value).toBe('Foo')
      expect(updateMock).not.toHaveBeenCalled()
    })
  }
}
