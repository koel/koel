import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { ref } from 'vue'
import { FilterKeywordsKey } from '@/symbols'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ListFilter.vue'

describe('listFilter.vue', () => {
  const h = createHarness()

  const renderComponent = (rawKeywords = '') => {
    const keywords = ref(rawKeywords)

    const rendered = h.render(Component, {
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

  it('mutates the injected reference', async () => {
    const { keywords } = renderComponent()

    await h.user.click(screen.getByTitle('Filter'))
    await h.user.type(screen.getByPlaceholderText('Keywords'), 'sample')

    expect(keywords.value).toBe('sample')
  })

  it('hides an empty text input on blur', async () => {
    renderComponent('sample')

    const input = screen.getByPlaceholderText('Keywords')
    await h.user.clear(input)
    await h.user.type(input, '[Tab]')

    expect(screen.queryByPlaceholderText('Keywords')).toBeNull()
  })
})
