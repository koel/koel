import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { useLocalStorage } from '@/composables/useLocalStorage'
import Component from './PlayableListHeaderActionMenu.vue'

describe('playableListHeaderActionMenu.vue', () => {
  const h = createHarness()

  it('contains proper items for song-only lists', () => {
    h.render(Component)

    ;['Title', 'Album', 'Artist', 'Track & Disc', 'Time', 'Date Added'].forEach(text => screen.getByText(text))
    ;['Podcast', 'Album or Podcast', 'Author', 'Artist or Author'].forEach(text =>
      expect(screen.queryByText(text)).toBeNull(),
    )
  })

  it('emits the sort event when an item is clicked', async () => {
    const { emitted } = h.render(Component)
    await h.user.click(screen.getByText('Title'))
    expect(emitted().sort[0]).toEqual(['title'])
  })

  it('contains proper items for episode-only lists', () => {
    h.render(Component, {
      props: {
        contentType: 'episodes',
      },
    })

    ;['Title', 'Podcast', 'Author', 'Time', 'Date Added'].forEach(text => screen.getByText(text))
    ;['Album', 'Album or Podcast', 'Artist', 'Artist or Author'].forEach(text =>
      expect(screen.queryByText(text)).toBeNull(),
    )
  })

  it('contains proper items for mixed-content lists', () => {
    h.render(Component, {
      props: {
        contentType: 'mixed',
      },
    })

    ;['Title', 'Album or Podcast', 'Artist or Author', 'Date Added'].forEach(text => screen.getByText(text))
    ;['Album', 'Artist', 'Podcast', 'Author'].forEach(text => expect(screen.queryByText(text)).toBeNull())
  })

  it('contains collaborative items when collaborative', () => {
    h.render(Component, {
      props: {
        collaborative: true,
      },
    })

    ;['User', 'Contributed'].forEach(text => screen.getByText(text))
  })

  it('does not contain collaborative items when not collaborative', () => {
    h.render(Component)

    ;['User', 'Contributed'].forEach(text => expect(screen.queryByText(text)).toBeNull())
  })

  it('sorts by collaborative columns', async () => {
    const { emitted } = h.render(Component, {
      props: {
        collaborative: true,
      },
    })

    await h.user.click(screen.getByText('User'))
    expect(emitted().sort[0]).toEqual(['collaboration.user.name'])

    await h.user.click(screen.getByText('Contributed'))
    expect(emitted().sort[1]).toEqual(['collaboration.added_at'])
  })

  it('has toggleable checkboxes for collaborative columns', async () => {
    h.actingAsUser().render(Component, {
      props: {
        collaborative: true,
      },
    })

    ;['User', 'Contributed'].forEach(text => screen.getByTitle(`Click to toggle the ${text} column`))
  })

  it('has custom order sort if so configured', () => {
    h.render(Component, {
      props: {
        hasCustomOrderSort: true,
      },
    })

    screen.getByText('Custom Order')
  })

  it('does not sort if the list is not sortable', async () => {
    const { emitted } = h.render(Component, {
      props: {
        sortable: false,
      },
    })

    await h.user.click(screen.getByText('Title'))
    expect(emitted().sort).toBeUndefined()
  })

  it('has a checkbox to toggle the column visibility', async () => {
    h.actingAsUser().render(Component)

    ;['Album', 'Track & Disc', 'Time'].forEach(text => screen.getByTitle(`Click to toggle the ${text} column`))

    await h.user.click(screen.getByTitle('Click to toggle the Album column'))

    expect(useLocalStorage().get('playable-list-columns')).toEqual(<PlayableListColumnName[]>[
      'track',
      'title',
      'artist',
      'duration',
      'playlist_collaborator',
      'playlist_added_at',
    ])
  })

  it('gets the column visibility from local storage', async () => {
    // ensure the localstorage is properly namespaced
    h.actingAsUser()

    useLocalStorage().set('playable-list-columns', ['track'])
    h.render(Component)

    ;[
      {
        title: 'Track & Disc',
        checked: true,
      },
      {
        title: 'Album',
        checked: false,
      },
      {
        title: 'Time',
        checked: true,
      },
    ].forEach(({ title, checked }) => {
      const el: HTMLInputElement = screen.getByTitle(`Click to toggle the ${title} column`)
      expect(el.checked).toBe(checked)
    })
  })
})
