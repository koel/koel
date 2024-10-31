import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { useLocalStorage } from '@/composables/useLocalStorage'
import Component from './SongListHeaderActionMenu.vue'

new class extends UnitTestCase {
  protected test () {
    it('contains proper items for song-only lists', () => {
      this.render(Component)

      ;['Title', 'Album', 'Artist', 'Track & Disc', 'Time', 'Date Added'].forEach(text => screen.getByText(text))
      ;['Podcast', 'Album or Podcast', 'Author', 'Artist or Author'].forEach(
        text => expect(screen.queryByText(text)).toBeNull(),
      )
    })

    it ('emits the sort event when an item is clicked', async () => {
      const { emitted } = this.render(Component)
      await this.user.click(screen.getByText('Title'))
      expect(emitted().sort[0]).toEqual(['title'])
    })

    it('contains proper items for episode-only lists', () => {
      this.render(Component, {
        props: {
          contentType: 'episodes',
        },
      })

      ;['Title', 'Podcast', 'Author', 'Time', 'Date Added'].forEach(text => screen.getByText(text))
      ;['Album', 'Album or Podcast', 'Artist', 'Artist or Author'].forEach(
        text => expect(screen.queryByText(text)).toBeNull(),
      )
    })

    it('contains proper items for mixed-content lists', () => {
      this.render(Component, {
        props: {
          contentType: 'mixed',
        },
      })

      ;['Title', 'Album or Podcast', 'Artist or Author', 'Date Added'].forEach(text => screen.getByText(text))
      ;['Album', 'Artist', 'Podcast', 'Author'].forEach(text => expect(screen.queryByText(text)).toBeNull())
    })

    it('has custom order sort if so configured', () => {
      this.render(Component, {
        props: {
          hasCustomOrderSort: true,
        },
      })

      screen.getByText('Custom Order')
    })

    it('does not sort if the list is not sortable', async () => {
      const { emitted } = this.render(Component, {
        props: {
          sortable: false,
        },
      })

      await this.user.click(screen.getByText('Title'))
      expect(emitted().sort).toBeUndefined()
    })

    it('has a checkbox to toggle the column visibility', async () => {
      this.be().render(Component)

      ;['Album', 'Track & Disc', 'Time'].forEach(text => screen.getByTitle(`Click to toggle the ${text} column`))

      await this.user.click(screen.getByTitle('Click to toggle the Album column'))

      expect(useLocalStorage().get('playable-list-columns')).toEqual(
        <PlayableListColumnName[]>['track', 'title', 'artist', 'duration'],
      )
    })

    it('gets the column visibility from local storage', async () => {
      // ensure the localstorage is properly namespaced
      this.be()

      useLocalStorage().set('playable-list-columns', ['track'])
      this.render(Component)

      ;[{
        title: 'Track & Disc',
        checked: true,
      }, {
        title: 'Album',
        checked: false,
      }, {
        title: 'Time',
        checked: true,
      }].forEach(({ title, checked }) => {
        const el: HTMLInputElement = screen.getByTitle(`Click to toggle the ${title} column`)
        expect(el.checked).toBe(checked)
      })
    })
  }
}
