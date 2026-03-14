import { describe, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './VisualizerScreen.vue'

vi.mock('@/stores/visualizerStore', () => ({
  visualizerStore: {
    all: [{ id: 'default', name: 'Default Visualizer', init: vi.fn() }],
    getVisualizerById: () => ({ id: 'default', name: 'Default Visualizer', init: vi.fn().mockResolvedValue(vi.fn()) }),
  },
}))

describe('visualizerScreen.vue', () => {
  const h = createHarness()

  it('renders visualizer selector', () => {
    h.render(Component)
    screen.getByText('Default Visualizer')
  })
})
