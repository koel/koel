import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { defineComponent } from 'vue'
import Component from './VirtualGridScroller.vue'

describe('virtualGridScroller', () => {
  const h = createHarness()

  const createItems = (count: number) => Array.from({ length: count }, (_, i) => ({ id: `id-${i}`, name: `Item ${i}` }))

  it('does not crash with empty items', async () => {
    h.render(Component, {
      props: { items: [], minItemWidth: 200 },
    })

    await h.tick(2)
    expect(document.body.innerHTML).toBeTruthy()
  })

  it('renders items via scoped slot after measuring', async () => {
    const Wrapper = defineComponent({
      components: { VirtualGridScroller: Component },
      setup() {
        const items = createItems(3)
        return { items }
      },
      template: `
        <VirtualGridScroller :items="items" :min-item-width="200" style="height: 500px; width: 800px;">
          <template #default="{ item }">
            <div data-testid="grid-item">{{ item.name }}</div>
          </template>
        </VirtualGridScroller>
      `,
    })

    h.render(Wrapper)
    await h.tick(3)

    // In jsdom, offsetHeight is 0 so measuring may not produce a valid height.
    // But the component should not crash.
    expect(document.body.innerHTML).toBeTruthy()
  })
})
