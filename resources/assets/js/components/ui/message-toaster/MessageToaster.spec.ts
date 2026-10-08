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

  it('announces every new message to screen readers, even when two arrive at once', async () => {
    const Announcer = defineComponent({
      components: { MessageToaster },
      setup: () => {
        const toaster = ref()

        const toastTwice = () => {
          toaster.value.success('Saved')
          toaster.value.info('Syncing')
        }

        return { toaster, toastTwice }
      },
      template: `
        <MessageToaster ref="toaster" />
        <button type="button" @click="toastTwice">Toast</button>
      `,
    })

    h.render(Announcer)
    await h.user.click(screen.getByRole('button'))
    await h.tick()

    const announcements = Array.from(screen.getByTestId('toast-announcements').children).map(line => line.textContent)
    expect(announcements).toEqual(['Saved', 'Syncing'])
  })
})
