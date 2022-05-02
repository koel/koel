import Component from './AlbumCard.vue'
import { cleanup, fireEvent, render } from '@testing-library/vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { commonStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import { mockHelper } from '@/__tests__/__helpers__'
import factory from '@/__tests__/factory'

describe('AlbumCard', () => {
  let album: Album

  beforeEach(() => {
    vi.restoreAllMocks()
    mockHelper.restoreMocks()
    cleanup()

    album = factory<Album>('album', {
      name: 'IV',
      songs: factory<Song>('song', 10)
    })

    commonStore.state.allowDownload = true
  })

  it('renders', () => {
    const { getByText, getByTestId } = render(Component, {
      props: {
        album
      }
    })

    expect(getByTestId('name').innerText).equal('IV')
    getByText(/^10 songs • .+ 0 plays$/)
    getByTestId('shuffleAlbum')
    getByTestId('downloadAlbum')
  })

  it('downloads', async () => {
    const mock = mockHelper.mock(downloadService, 'fromAlbum')
    const { getByTestId } = render(Component, {
      props: {
        album
      }
    })
    await fireEvent.click(getByTestId('downloadAlbum'))
    expect(mock).toHaveBeenCalledTimes(1)
  })

  it('shuffles', async () => {
    const mock = mockHelper.mock(playbackService, 'playAllInAlbum')

    const { getByTestId } = render(Component, {
      props: {
        album
      }
    })

    await fireEvent.click(getByTestId('shuffleAlbum'))
    expect(mock).toHaveBeenCalled()
  })
})
