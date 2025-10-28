import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './LyricsPane.vue'
import Magnifier from '@/components/ui/Magnifier.vue'
import SyncLyricsPane from '@/components/ui/SyncLyricsPane.vue'

describe('lyricsPane.vue', () => {
  const h = createHarness()

  const renderComponent = (song?: Song) => {
    song = song || h.factory('song', {
      lyrics: 'Foo bar baz qux',
    })

    const rendered = h.render(Component, {
      props: {
        song,
      },
      global: {
        stubs: {
          Magnifier,
          SyncLyricsPane,
        },
      },
    })

    return {
      ...rendered,
      song,
    }
  }

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())

  it('renders plain text lyrics when lyrics are not synced', () => {
    const { container } = renderComponent(h.factory('song', {
      lyrics: 'Plain lyrics\nLine 2\nLine 3',
    }))

    expect(container.querySelector('.lyrics')).toBeTruthy()
    expect(container.querySelector('.lyrics-synced')).toBeFalsy()
  })

  it('renders SyncLyricsPane when lyrics are in LRC format', () => {
    const song = h.factory('song', {
      lyrics: '[00:12.00]First line\n[00:17.20]Second line\n[00:21.00]Third line',
    })

    const { container } = h.render(Component, {
      props: { song },
      global: {
        stubs: {
          Magnifier,
        },
      },
    })

    expect(container.querySelector('.lyrics')).toBeFalsy()
    expect(container.querySelector('.lyrics-synced')).toBeTruthy()
  })

  it('provides a button to add lyrics if current user is admin', async () => {
    const song = h.factory('song', { lyrics: null })

    const mock = h.mock(eventBus, 'emit')
    h.actingAsAdmin()
    renderComponent(song)

    await h.user.click(screen.getByRole('button', { name: 'Click here' }))

    expect(mock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_SONG_FORM', song, 'lyrics')
  })

  it('does not have a button to add lyrics if current user is not an admin', async () => {
    h.actingAsUser()
    renderComponent(h.factory('song', { lyrics: null }))
    expect(screen.queryByRole('button', { name: 'Click here' })).toBeNull()
  })

  it('handles lines without timestamps in LRC format', () => {
    const song = h.factory('song', {
      lyrics: '[00:12.00]First line\nLine without timestamp\n[00:21.00]Third line',
    })

    const { container } = h.render(Component, {
      props: { song },
      global: {
        stubs: {
          Magnifier,
        },
      },
    })

    // Should still render as synced lyrics
    expect(container.querySelector('.lyrics-synced')).toBeTruthy()
    // Should have 3 lines (including the one without timestamp)
    const lines = container.querySelectorAll('.lyrics-line')
    expect(lines.length).toBe(3)
  })

  it('assigns 00:00:00 to first line if it has no timestamp', () => {
    const song = h.factory('song', {
      lyrics: 'First line without timestamp\n[00:12.00]Second line\n[00:21.00]Third line',
    })

    const { container } = h.render(Component, {
      props: { song },
      global: {
        stubs: {
          Magnifier,
        },
      },
    })

    expect(container.querySelector('.lyrics-synced')).toBeTruthy()
    const lines = container.querySelectorAll('.lyrics-line')
    expect(lines.length).toBe(3)
  })

  it('assigns previous timestamp to last line if it has no timestamp', () => {
    const song = h.factory('song', {
      lyrics: '[00:12.00]First line\n[00:21.00]Second line\nLast line without timestamp',
    })

    const { container } = h.render(Component, {
      props: { song },
      global: {
        stubs: {
          Magnifier,
        },
      },
    })

    expect(container.querySelector('.lyrics-synced')).toBeTruthy()
    const lines = container.querySelectorAll('.lyrics-line')
    expect(lines.length).toBe(3)
  })
})
