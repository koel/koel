import factory from '@/__tests__/factory'
import PlaylistSidebarItem from '@/components/playlist/PlaylistSidebarItem.vue'
import { beforeEach, expect, it } from 'vitest'
import { mockHelper, render, stub } from '@/__tests__/__helpers__'
import { cleanup, fireEvent } from '@testing-library/vue'

let playlist: Playlist

beforeEach(() => {
  cleanup()
  mockHelper.restoreAllMocks()
  playlist = factory<Playlist>('playlist', {
    id: 99,
    name: 'A Standard Playlist'
  })
})

const renderComponent = (playlist: Record<string, any>, type: PlaylistType = 'playlist') => {
  return render(PlaylistSidebarItem, {
    props: {
      playlist,
      type
    },
    global: {
      stubs: {
        NameEditor: stub('name-editor')
      }
    }
  })
}

it('edits the name of a standard playlist', async () => {
  const { getByTestId, queryByTestId } = renderComponent(playlist)
  expect(await queryByTestId('name-editor')).toBeNull()

  await fireEvent.dblClick(getByTestId('playlist-sidebar-item'))

  getByTestId('name-editor')
})

it('does not allow editing the name of the "Favorites" playlist', async () => {
  const { getByTestId, queryByTestId } = renderComponent({
    name: 'Favorites',
    songs: []
  }, 'favorites')

  expect(await queryByTestId('name-editor')).toBeNull()

  await fireEvent.dblClick(getByTestId('playlist-sidebar-item'))

  expect(await queryByTestId('name-editor')).toBeNull()
})

it('does not allow editing the name of the "Recently Played" playlist', async () => {
  const { getByTestId, queryByTestId } = renderComponent({
    name: 'Recently Played',
    songs: []
  }, 'recently-played')

  expect(await queryByTestId('name-editor')).toBeNull()

  await fireEvent.dblClick(getByTestId('playlist-sidebar-item'))

  expect(await queryByTestId('name-editor')).toBeNull()
})
