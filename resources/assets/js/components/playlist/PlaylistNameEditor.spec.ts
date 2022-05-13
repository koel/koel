import factory from '@/__tests__/factory'
import PlaylistNameEditor from '@/components/playlist/PlaylistNameEditor.vue'
import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import { playlistStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'

new class extends UnitTestCase {
  private useEditor () {
    const updateMock = this.mock(playlistStore, 'update')

    const { getByTestId } = this.render(PlaylistNameEditor, {
      props: {
        playlist: factory<Playlist>('playlist', {
          id: 99,
          name: 'Foo'
        })
      }
    })

    return {
      updateMock,
      input: getByTestId<HTMLInputElement>('inline-playlist-name-input')
    }
  }

  protected test () {
    it('updates a playlist name on blur', async () => {
      const { updateMock, input } = this.useEditor()

      await fireEvent.update(input, 'Bar')
      await fireEvent.blur(input)

      expect(updateMock).toHaveBeenCalledWith(expect.objectContaining({
        id: 99,
        name: 'Bar'
      }))
    })

    it('updates a playlist name on enter', async () => {
      const { updateMock, input } = this.useEditor()

      await fireEvent.update(input, 'Bar')
      await fireEvent.keyUp(input, { key: 'Enter' })

      expect(updateMock).toHaveBeenCalledWith(expect.objectContaining({
        id: 99,
        name: 'Bar'
      }))
    })

    it('cancels updating on esc', async () => {
      const { updateMock, input } = this.useEditor()

      await fireEvent.update(input, 'Bar')
      await fireEvent.keyUp(input, { key: 'Esc' })

      expect(input.value).toBe('Foo')
      expect(updateMock).not.toHaveBeenCalled()
    })
  }
}
