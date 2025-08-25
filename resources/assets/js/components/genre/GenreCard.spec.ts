import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils/eventBus'
import Component from './GenreCard.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('requests context menu', async () => {
      const { genre } = this.renderComponent()
      const emitMock = this.mock(eventBus, 'emit')
      await this.trigger(screen.getByRole('listitem'), 'contextMenu')

      expect(emitMock).toHaveBeenCalledWith('GENRE_CONTEXT_MENU_REQUESTED', expect.any(MouseEvent), genre)
    })
  }

  private createGenre (overrides: Partial<Genre> = {}): Genre {
    return factory('genre', {
      id: 'foo',
      name: 'Classical',
      song_count: 99,
      ...overrides,
    })
  }

  private renderComponent (genre?: Genre) {
    genre = genre || this.createGenre()

    const render = this.render(Component, {
      props: {
        genre: genre || this.createGenre(),
      },
    })

    return {
      ...render,
      genre,
    }
  }
}
