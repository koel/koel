import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { ref } from 'vue'
import { FilterKeywordsKey } from '@/symbols'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './ListFilter.vue'

new class extends UnitTestCase {
  private renderComponent (rawKeywords = '') {
    const keywords = ref(rawKeywords)

    const rendered = this.render(Component, {
      global: {
        provide: {
          [FilterKeywordsKey]: keywords,
        },
      },
    })

    return {
      ...rendered,
      keywords,
    }
  }

  protected test () {
    it('mutates the injected reference', async () => {
      const { keywords } = this.renderComponent()

      await this.user.click(screen.getByTitle('Filter'))
      await this.user.type(screen.getByPlaceholderText('Keywords'), 'sample')

      expect(keywords.value).toBe('sample')
    })

    it('hides an empty text input on blur', async () => {
      this.renderComponent('sample')

      const input = screen.getByPlaceholderText('Keywords')
      await this.user.clear(input)
      await this.user.type(input, '[Tab]')

      expect(screen.queryByPlaceholderText('Keywords')).toBeNull()
    })
  }
}
