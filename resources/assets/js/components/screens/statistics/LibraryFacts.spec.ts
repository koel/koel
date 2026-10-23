import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './LibraryFacts.vue'

describe('libraryFacts.vue', () => {
  const h = createHarness()

  const makeFact = (overrides: Partial<LibraryFact> = {}): LibraryFact => ({
    key: 'songs',
    label: 'Songs',
    value: 1200,
    unit: 'count',
    used: null,
    limit: null,
    ...overrides,
  })

  it('shows every fact in the given order', () => {
    h.render(Component, {
      props: {
        facts: [
          makeFact({ key: 'songs', label: 'Songs' }),
          makeFact({ key: 'albums', label: 'Albums' }),
          makeFact({ key: 'storage', label: 'Storage used', unit: 'bytes' }),
        ],
      },
    })

    expect(screen.getAllByRole('term').map(term => term.textContent)).toEqual(['Songs', 'Albums', 'Storage used'])
  })

  it('measures a fact against its limit', () => {
    h.render(Component, {
      props: {
        facts: [makeFact(), makeFact({ key: 'storage', unit: 'bytes', value: 40, used: 30, limit: 100 })],
      },
    })

    const meter = screen.getByRole('meter')

    expect([meter.getAttribute('aria-valuenow'), meter.getAttribute('aria-valuemax')]).toEqual(['30', '100'])
  })
})
