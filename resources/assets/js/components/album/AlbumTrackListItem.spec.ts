import { cleanup, fireEvent } from '@testing-library/vue'
import { beforeEach, expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { mockHelper, render } from '@/__tests__/__helpers__'
import { commonStore, queueStore, songStore } from '@/stores'
import { playbackService } from '@/services'
import AlbumTrackListItem from './AlbumTrackListItem.vue'

let song: Song

const track = {
  title: 'Fahrstuhl to Heaven',
  fmtLength: '00:42'
}

const album = factory<Album>('album', { id: 42 })

beforeEach(() => {
  cleanup()
  mockHelper.restoreAllMocks()

  song = factory<Song>('song')
  commonStore.state.useiTunes = true
})

it('renders', () => {
  const { html } = render(AlbumTrackListItem, {
    props: {
      album,
      track
    }
  })

  expect(html()).toMatchSnapshot()
})

it('plays', async () => {
  const guessMock = mockHelper.mock(songStore, 'guess', song)
  const queueMock = mockHelper.mock(queueStore, 'queueIfNotQueued')
  const playMock = mockHelper.mock(playbackService, 'play')

  const { getByTitle } = render(AlbumTrackListItem, {
    props: {
      album,
      track
    }
  })

  await fireEvent.click(getByTitle('Click to play'))

  expect(guessMock).toHaveBeenCalledWith('Fahrstuhl to Heaven', album)
  expect(queueMock).toHaveBeenNthCalledWith(1, song)
  expect(playMock).toHaveBeenNthCalledWith(1, song)
})
