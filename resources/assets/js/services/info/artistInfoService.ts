import { httpService } from '..'

export const artistInfoService = {
  async fetch (artist: Artist): Promise<Artist> {
    if (!artist.info) {
      const info = await httpService.get<ArtistInfo|null>(`artist/${artist.id}/info`)

      if (info) {
        this.merge(artist, info)
      }
    }

    return artist
  },

  /**
   * Merge the (fetched) info into an artist.
   */
  merge: (artist: Artist, info: ArtistInfo): void => {
    // If the artist image is not in a nice form, discard.
    if (typeof info.image !== 'string') {
      info.image = null
    }

    // Set the artist image on the client side to the retrieved image from server.
    if (info.image) {
      artist.image = info.image
    }

    artist.info = info
  }
}
