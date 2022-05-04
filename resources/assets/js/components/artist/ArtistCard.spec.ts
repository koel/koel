import { mockHelper, render } from '@/__tests__/__helpers__'
import { cleanup, fireEvent } from '@testing-library/vue'
import { beforeEach, expect, it, vi } from 'vitest'
import factory from '@/__tests__/factory'
import { commonStore } from '@/stores'
import ArtistCard from './ArtistCard.vue'
import { downloadService, playbackService } from '@/services'

let artist: Artist

beforeEach(() => {
  vi.restoreAllMocks()
  mockHelper.restoreAllMocks()
  cleanup()

  artist = factory<Artist>('artist', {
    id: 3, // make sure it's not "Various Artists"
    name: 'Led Zeppelin',
    albums: factory<Album>('album', 4),
    songs: factory<Song>('song', 16)
  })

  commonStore.state.allowDownload = true
})

it('renders', () => {
  const { getByText, getByTestId } = render(ArtistCard, {
    props: {
      artist
    }
  })

  expect(getByTestId('name').innerText).equal('Led Zeppelin')
  getByText(/^4 albums\s+•\s+16 songs.+0 plays$/)
  getByTestId('shuffle-artist')
  getByTestId('download-artist')
})

it('downloads', async () => {
  const mock = mockHelper.mock(downloadService, 'fromArtist')

  const { getByTestId } = render(ArtistCard, {
    props: {
      artist
    }
  })

  await fireEvent.click(getByTestId('download-artist'))
  expect(mock).toHaveBeenCalledTimes(1)
})

it('shuffles', async () => {
  const mock = mockHelper.mock(playbackService, 'playAllByArtist')

  const { getByTestId } = render(ArtistCard, {
    props: {
      artist
    }
  })

  await fireEvent.click(getByTestId('shuffle-artist'))
  expect(mock).toHaveBeenCalled()
})
