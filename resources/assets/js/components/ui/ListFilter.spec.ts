import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { ref } from 'vue'
import { FilterKeywordsKey } from '@/symbols'
import UnitTestCase from '@/__tests__/UnitTestCase'
import ListFilter from './ListFilter.vue'

new class extends UnitTestCase {
  protected test () {
    it('mutates the injected reference', async () => {
      const keywords = ref('')

      this.render(ListFilter, {
        global: {
          provide: {
            [FilterKeywordsKey]: keywords,
          },
        },
      })

      await this.user.click(screen.getByTitle('Filter'))
      await this.user.type(screen.getByPlaceholderText('Keywords'), 'sample')

      expect(keywords.value).toBe('sample')
    })

    it('hides an empty text input on blur', async () => {
      const keywords = ref('sample')

      this.render(ListFilter, {
        global: {
          provide: {
            [FilterKeywordsKey]: keywords,
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
