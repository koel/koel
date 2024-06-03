import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './SongListSorter.vue'

new class extends UnitTestCase {
  protected test () {
    it('contains proper items for song-only lists', () => {
      this.render(Component)

      ;['Title', 'Album', 'Artist', 'Track & Disc', 'Time', 'Date Added'].forEach(text => screen.getByText(text))
      ;['Podcast', 'Album or Podcast', 'Author', 'Artist or Author'].forEach(
        text => expect(screen.queryByText(text)).toBeNull()
      )
    })

    it('contains proper items for episode-only lists', () => {
      this.render(Component, {
        props: {
          contentType: 'episodes'
        }
      })

      ;['Title', 'Podcast', 'Author', 'Time', 'Date Added'].forEach(text => screen.getByText(text))
      ;['Album', 'Album or Podcast', 'Artist', 'Artist or Author'].forEach(
        text => expect(screen.queryByText(text)).toBeNull()
      )
    })

    it('contains proper items for mixed-content lists', () => {
      this.render(Component, {
        props: {
          contentType: 'mixed'
        }
      })

      ;['Title', 'Album or Podcast', 'Artist or Author', 'Date Added'].forEach(text => screen.getByText(text))
      ;['Album', 'Artist', 'Podcast', 'Author'].forEach(text => expect(screen.queryByText(text)).toBeNull())
    })

    it('has custom order sort if so configured', () => {
      this.render(Component, {
        props: {
          hasCustomOrderSort: true
        }
      })

      screen.getByText('Custom Order')
    })
  }
}
