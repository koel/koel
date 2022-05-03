import { render } from '@/__tests__/__helpers__'
import factory from '@/__tests__/factory'
import { cleanup } from '@testing-library/vue'
import { beforeEach, expect, it } from 'vitest'
import AlbumTrackList from './AlbumTrackList.vue'
import TrackListItem from './AlbumTrackListItem.vue'

beforeEach(() => cleanup())

it('lists the correct number of tracks', () => {
  const { queryAllByTestId } = render(AlbumTrackList, {
    props: {
      album: factory<Album>('album')
    },
    global: {
      stubs: {
        TrackListItem
      }
    }
  })

  expect(queryAllByTestId('album-track-item')).toHaveLength(2)
})
