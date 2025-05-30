import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { ref } from 'vue'
import { PlayableListFilterKeywordsKey } from '@/symbols'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SongListFilter from './SongListFilter.vue'

new class extends UnitTestCase {
  protected test () {
    it('mutates the injected reference', async () => {
      const keywords = ref('')

      this.render(SongListFilter, {
        global: {
          provide: {
            [PlayableListFilterKeywordsKey]: keywords,
          },
        },
      })

      await this.user.click(screen.getByTitle('Filter'))
      await this.user.type(screen.getByPlaceholderText('Keywords'), 'sample')

      expect(keywords.value).toBe('sample')
    })

    it('hides an empty text input on blur', async () => {
      const keywords = ref('sample')

      this.render(SongListFilter, {
        global: {
          provide: {
            [PlayableListFilterKeywordsKey]: keywords,
          },
        },
      })

      const input = screen.getByPlaceholderText('Keywords')
      await this.user.clear(input)
      await this.user.type(input, '[Tab]')

      expect(screen.queryByPlaceholderText('Keywords')).toBeNull()
    })
  }
}
