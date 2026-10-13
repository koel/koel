import { ref } from 'vue'
import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { CurrentStreamableKey } from '@/config/symbols'
import Component from './index.vue'

describe('mainWrapper', () => {
  const h = createHarness()

  it('moves focus to the main content with the skip link', async () => {
    h.render(Component, {
      global: {
        provide: {
          [<symbol>CurrentStreamableKey]: ref(h.factory('song').make()),
        },
        stubs: {
          SideBar: h.stub('sidebar'),
          SideSheet: h.stub('side-sheet'),
          ModalWrapper: h.stub('modal-wrapper'),
          HomeScreen: h.stub('home-screen'),
        },
      },
    })

    const skipLink = screen.getAllByRole('link')[0]
    const target = document.querySelector(skipLink.getAttribute('href')!)

    await h.user.click(skipLink)

    expect(target).not.toBeNull()
    expect(document.activeElement).toBe(target)
  })
})
