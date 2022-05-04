import { beforeEach, expect, it } from 'vitest'
import { render } from '@/__tests__/__helpers__'
import { cleanup, fireEvent } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import ArtistInfo from './ArtistInfo.vue'
import ArtistThumbnail from '@/components/ui/AlbumArtistThumbnail.vue'

let artist: Artist

beforeEach(() => cleanup())

it.each([['sidebar'], ['full']])('renders in %s mode', async (mode: string) => {
  const { getByTestId } = render(ArtistInfo, {
    props: {
      artist: factory<Artist>('artist'),
      mode
    },
    global: {
      stubs: {
        ArtistThumbnail
      }
    }
  })

  getByTestId('album-artist-thumbnail')

  const element = getByTestId<HTMLElement>('artist-info')
  expect(element.classList.contains(mode)).toBe(true)
})

it('triggers showing full wiki', async () => {
  const artist = factory<Artist>('artist')

  const { getByText } = render(ArtistInfo, {
    props: {
      artist
    }
  })

  await fireEvent.click(getByText('Full Bio'))
  getByText(artist.info!.bio!.full)
})
