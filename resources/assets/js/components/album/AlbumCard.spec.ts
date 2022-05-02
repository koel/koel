import Component from './AlbumCard.vue'
import { cleanup, fireEvent, render } from '@testing-library/vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import factory from '../../__tests__/factory'
import { commonStore } from '@/stores'
import { downloadService, playbackService } from '@/services'

describe('AlbumCard', () => {
  let album: Album

  beforeEach(() => {
    vi.restoreAllMocks()
    cleanup()

    album = factory('album', {
      name: 'IV',
      songs: factory('song', 10)
    })

    commonStore.state.allowDownload = true
  })

  it('renders', () => {
    const { getByText, getByTestId } = render(Component, {
      propsData: {
        album
      }
    })

    expect(getByTestId('name').innerText).equal('IV')
    getByText(/^10 songs • .+ 0 plays$/)
    getByTestId('shuffleAlbum')
    getByTestId('downloadAlbum')
  })

  it('downloads', async () => {
    const spy = vi.spyOn(downloadService, 'fromAlbum')
    const { getByTestId } = render(Component, {
      propsData: {
        album
      }
    })
    await fireEvent.click(getByTestId('downloadAlbum'))
    expect(spy).toHaveBeenCalledTimes(1)
  })

  it('shuffles', async () => {
    const mock = vi.spyOn(playbackService, 'playAllInAlbum')

    const { getByTestId } = render(Component, {
      propsData: {
        album
      }
    })

    await fireEvent.click(getByTestId('shuffleAlbum'))
    expect(mock).toHaveBeenCalled()
  })
})
