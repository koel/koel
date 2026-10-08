import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { defineComponent, ref } from 'vue'
import MessageToaster from './MessageToaster.vue'

describe('messageToaster', () => {
  const h = createHarness()

  const Wrapper = defineComponent({
    components: { MessageToaster },
    setup() {
      const toaster = ref()
      return { toaster }
    },
    template: '<MessageToaster ref="toaster" />',
  })

  it('has no messages initially', () => {
    h.render(Wrapper)
    const toasterEl = document.querySelector('.popover')!
    expect(toasterEl.querySelectorAll('li')).toHaveLength(0)
  })

  it('announces a new message to screen readers', async () => {
    const Announcer = defineComponent({
      components: { MessageToaster },
      setup: () => ({ toaster: ref() }),
      template: `
        <MessageToaster ref="toaster" />
        <button type="button" @click="toaster.success('Added to Favorites')">Toast</button>
      `,
    })

    h.render(Announcer)
    await h.user.click(screen.getByRole('button'))
    await h.tick()

    expect(screen.getByRole('status').textContent).toBe('Added to Favorites')
  })
})
