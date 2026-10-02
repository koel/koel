import { describe, expect, it } from 'vite-plus/test'
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
})
