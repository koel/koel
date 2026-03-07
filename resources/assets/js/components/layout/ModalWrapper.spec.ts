import { describe, expect, it, vi } from 'vitest'
import { markRaw, shallowRef } from 'vue'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ModalWrapper.vue'

const modalOptions = shallowRef<any>({
  component: null,
})

vi.mock('@/utils/helpers', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/helpers')>()),
  requireInjection: () => modalOptions,
}))

describe('modalWrapper.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      modalOptions.value = { component: null }
    },
  })

  it('shows a modal component', async () => {
    h.render(Component)

    modalOptions.value = {
      component: markRaw(h.stub('test-modal')),
      props: {},
    }

    await waitFor(() => screen.getByTestId('test-modal'))
  })

  it('passes props to the modal component', async () => {
    h.render(Component)

    modalOptions.value = {
      component: markRaw(h.stub('test-modal')),
      props: { foo: 'bar' },
    }

    await waitFor(() => {
      const el = screen.getByTestId('test-modal')
      expect(el).toBeTruthy()
    })
  })

  it('closes modal on close event', async () => {
    h.render(Component)

    modalOptions.value = {
      component: markRaw(h.stub('test-modal')),
      props: {},
    }

    await waitFor(() => screen.getByTestId('test-modal'))

    // simulate close
    modalOptions.value = { component: null }

    await waitFor(() => expect(screen.queryByTestId('test-modal')).toBeNull())
  })
})
