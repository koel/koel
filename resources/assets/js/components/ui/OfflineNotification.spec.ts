import { describe, expect, it, vi } from 'vitest'
import { nextTick, ref } from 'vue'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'

const onlineMock = ref(false)

vi.mock('@/composables/useNetworkStatus', () => ({
  useNetworkStatus: () => ({
    online: onlineMock,
  }),
}))

import Component from './OfflineNotification.vue'

describe('offlineNotification', () => {
  const h = createHarness({
    beforeEach: () => {
      onlineMock.value = false
    },
  })

  it('shows offline message', () => {
    h.render(Component)
    screen.getByText("You're offline.")
  })

  it('can be dismissed', async () => {
    h.render(Component)
    await h.user.click(screen.getByTitle('Click to dismiss'))
    expect(screen.queryByText("You're offline.")).toBeNull()
  })

  it('re-shows when going offline again after dismiss', async () => {
    h.render(Component)
    await h.user.click(screen.getByTitle('Click to dismiss'))
    expect(screen.queryByText("You're offline.")).toBeNull()

    // Go online then offline again
    onlineMock.value = true
    await nextTick()
    onlineMock.value = false
    await nextTick()

    await waitFor(() => screen.getByText("You're offline."))
  })
})
