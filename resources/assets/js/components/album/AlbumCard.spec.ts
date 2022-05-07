import { cleanup, fireEvent } from '@testing-library/vue'
import { beforeEach, expect, it } from 'vitest'
import { commonStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import { mockHelper, render } from '@/__tests__/__helpers__'
import factory from '@/__tests__/factory'
import AlbumCard from './AlbumCard.vue'

let album: Album

beforeEach(() => {
  mockHelper.restoreAllMocks()
  cleanup()

  album = factory<Album>('album', {
    name: 'IV',
    songs: factory<Song>('song', 10)
  })

  commonStore.state.allowDownload = true
})

it('renders', () => {
  const { getByText, getByTestId } = render(AlbumCard, {
    props: {
      album
    }
  })

  expect(getByTestId('name').innerText).equal('IV')
  getByText(/^10 songs.+0 plays$/)
  getByTestId('shuffle-album')
  getByTestId('download-album')
})

it('downloads', async () => {
  const mock = mockHelper.mock(downloadService, 'fromAlbum')

  const { getByTestId } = render(AlbumCard, {
    props: {
      album
    }
  })

  await fireEvent.click(getByTestId('download-album'))
  expect(mock).toHaveBeenCalledTimes(1)
})

it('shuffles', async () => {
  const mock = mockHelper.mock(playbackService, 'playAllInAlbum')

  const { getByTestId } = render(AlbumCard, {
    props: {
      album
    }
  })

  await fireEvent.click(getByTestId('shuffle-album'))
  expect(mock).toHaveBeenCalled()
})
