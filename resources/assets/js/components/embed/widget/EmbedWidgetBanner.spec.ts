import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import Component from './EmbedWidgetBanner.vue'

describe('embedWidgetBanner.vue', async () => {
  const h = createHarness()

  const renderComponent = (embed: WidgetReadyEmbed, options?: EmbedOptions) => {
    options = options || {
      theme: 'classic',
      layout: 'full',
      preview: false,
    }

    const rendered = h.render(Component, {
      props: {
        embed,
        options,
      },
      global: {
        stubs: {
          Thumbnail: h.stub('thumbnail'),
          PreviewBadge: h.stub('preview-badge'),
        },
      },
    })

    return {
      ...rendered,
      embed,
      options,
    }
  }

  it('renders a song', () => {
    const song = h.factory('song', {
      title: 'Bohemian Rhapsody',
      artist_name: 'Queen',
      album_name: 'A Night at the Opera',
      track: 9,
    })

    const embed: WidgetReadyEmbed = {
      ...h.factory('embed', {
        embeddable_type: 'playable',
        embeddable_id: song.id,
      }),
      embeddable: song,
      playables: [song],
    }

    renderComponent(embed)

    screen.getByText('Bohemian Rhapsody')
    screen.getByText('Queen')
    expect(screen.queryByTestId('preview-badge')).toBeNull()
  })

  it('renders a podcast episode', () => {
    const episode = h.factory('episode', {
      title: 'How to tell people to shut up about Queen',
      podcast_title: 'The Everyday Guide',
      podcast_author: 'The Everyday Guy',
    })

    const embed: WidgetReadyEmbed = {
      ...h.factory('embed', {
        embeddable_type: 'playable',
        embeddable_id: episode.id,
      }),
      embeddable: episode,
      playables: [episode],
    }

    renderComponent(embed)

    screen.getByText('How to tell people to shut up about Queen')
    screen.getByText('The Everyday Guide')
    expect(screen.queryByTestId('preview-badge')).toBeNull()
  })

  it('renders a playlist', () => {
    const playlist = h.factory('playlist', {
      name: 'The Best of Queen',
      description: 'A collection of the best songs from Queen',
    })

    const embed: WidgetReadyEmbed = {
      ...h.factory('embed', {
        embeddable_type: 'playlist',
        embeddable_id: playlist.id,
      }),
      embeddable: playlist,
      playables: h.factory('song', 5),
    }

    renderComponent(embed)

    screen.getByText('The Best of Queen')
    screen.getByText('A collection of the best songs from Queen')
    expect(screen.queryByTestId('preview-badge')).toBeNull()
  })

  it('renders an album', () => {
    const album = h.factory('album', {
      name: 'A Night at the Opera',
      artist_name: 'Queen',
    })

    const embed: WidgetReadyEmbed = {
      ...h.factory('embed', {
        embeddable_type: 'album',
        embeddable_id: album.id,
      }),
      embeddable: album,
      playables: h.factory('song', 5),
    }

    renderComponent(embed)

    screen.getByText('A Night at the Opera')
    screen.getByText('Album by Queen')
    expect(screen.queryByTestId('preview-badge')).toBeNull()
  })

  it('renders an artist', () => {
    const artist = h.factory('artist', {
      name: 'Queen',
    })

    const embed: WidgetReadyEmbed = {
      ...h.factory('embed', {
        embeddable_type: 'artist',
        embeddable_id: artist.id,
      }),
      embeddable: artist,
      playables: h.factory('song', 5),
    }

    renderComponent(embed)

    screen.getByText('Queen')
    screen.getByText('Artist')
    expect(screen.queryByTestId('preview-badge')).toBeNull()
  })

  it('shows a preview badge if in preview mode', () => {
    const song = h.factory('song')

    const embed: WidgetReadyEmbed = {
      ...h.factory('embed', {
        embeddable_type: 'playable',
        embeddable_id: song.id,
      }),
      embeddable: song,
      playables: [song],
    }

    renderComponent(embed, {
      theme: 'classic',
      layout: 'full',
      preview: true,
    })

    screen.getByTestId('preview-badge')
  })
})
