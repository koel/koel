import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './LyricsPane.vue'

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
          Magnifier: h.stub('magnifier'),
          LrcLyricsPane: h.stub('lrc-lyrics-pane'),
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
    renderComponent(h.factory('song', {
      lyrics: 'Plain lyrics\nLine 2\nLine 3',
    }))

    screen.getByTestId('plain-text-lyrics')
    expect(screen.queryByTestId('lrc-lyrics-pane')).toBeNull()
  })

  it('renders LRC pane when lyrics are in LRC format', () => {
    const song = h.factory('song', {
      lyrics: '[00:12.00]First line\n[00:17.20]Second line\n[00:21.00]Third line',
    })

    renderComponent(song)

    expect(screen.queryByTestId('plain-text-lyrics')).toBeNull()
    screen.getByTestId('lrc-lyrics-pane')
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
})
