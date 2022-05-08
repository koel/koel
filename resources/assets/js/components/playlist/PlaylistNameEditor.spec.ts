import factory from '@/__tests__/factory'
import PlaylistNameEditor from '@/components/playlist/PlaylistNameEditor.vue'
import { beforeEach, expect, it } from 'vitest'
import { mockHelper, render } from '@/__tests__/__helpers__'
import { cleanup, fireEvent } from '@testing-library/vue'
import { playlistStore } from '@/stores'

beforeEach(() => {
  mockHelper.restoreAllMocks()
  cleanup()
})

const setup = () => {
  const updateMock = mockHelper.mock(playlistStore, 'update')

  const { getByTestId } = render(PlaylistNameEditor, {
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

it('updates a playlist name on blur', async () => {
  const { updateMock, input } = setup()

  await fireEvent.update(input, 'Bar')
  await fireEvent.blur(input)

  expect(updateMock).toHaveBeenCalledWith(expect.objectContaining({
    id: 99,
    name: 'Bar'
  }))
})

it('updates a playlist name on enter', async () => {
  const { updateMock, input } = setup()

  await fireEvent.update(input, 'Bar')
  await fireEvent.keyUp(input, { key: 'Enter' })

  expect(updateMock).toHaveBeenCalledWith(expect.objectContaining({
    id: 99,
    name: 'Bar'
  }))
})

it('cancels updating on esc', async () => {
  const { updateMock, input } = setup()

  await fireEvent.update(input, 'Bar')
  await fireEvent.keyUp(input, { key: 'Esc' })

  expect(input.value).toBe('Foo')
  expect(updateMock).not.toHaveBeenCalled()
})
