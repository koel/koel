import { beforeEach, expect, it, vi } from 'vitest'
import { mockHelper, render } from '@/__tests__/__helpers__'
import { cleanup, fireEvent } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import AlbumInfo from '@/components/album/AlbumInfo.vue'
import AlbumThumbnail from '@/components/ui/AlbumArtistThumbnail.vue'

let album: Album

beforeEach(() => {
  vi.restoreAllMocks()
  mockHelper.restoreMocks()
  cleanup()

  album = factory<Album>('album', {
    name: 'IV',
    songs: factory<Song>('song', 10)
  })
})

it.each([['sidebar'], ['full']])('renders in %s mode', async (mode: string) => {
  const { getByTestId } = render(AlbumInfo, {
    props: {
      album,
      mode
    },
    global: {
      stubs: {
        AlbumThumbnail
      }
    }
  })

  getByTestId('album-thumbnail')

  const element = getByTestId<HTMLElement>('album-info')
  expect(element.classList.contains(mode)).toBe(true)
})

it('triggers showing full wiki', async () => {
  const { getByText } = render(AlbumInfo, {
    props: {
      album
    }
  })

  await fireEvent.click(getByText('Full Wiki'))
  getByText(album.info!.wiki!.full)
})
