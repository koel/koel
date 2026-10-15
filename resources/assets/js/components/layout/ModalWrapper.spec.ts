import { describe, expect, it, vi } from 'vite-plus/test'
import { defineComponent, h as createElement, markRaw, shallowRef, withKeys } from 'vue'
import { fireEvent, screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useForm } from '@/composables/useForm'
import { ModalKey } from '@/config/symbols'
import Component from './ModalWrapper.vue'

const modalOptions = shallowRef<any>({
  component: null,
})

vi.mock('@/utils/helpers', async importOriginal => {
  const helpers = await importOriginal<typeof import('@/utils/helpers')>()

  return {
    ...helpers,
    requireInjection: ((key, defaultValue) =>
      key === ModalKey ? modalOptions : helpers.requireInjection(key, defaultValue)) as typeof helpers.requireInjection,
  }
})

const mockShowConfirmDialog = vi.fn()

vi.mock('@/composables/useDialogBox', () => ({
  useDialogBox: () => ({
    showConfirmDialog: mockShowConfirmDialog,
  }),
}))

const TestForm = defineComponent({
  emits: ['close'],
  setup(_props, { emit }) {
    const { data, maybeClose } = useForm({
      initialValues: { name: '' },
      onSubmit: async () => {},
      onClose: () => emit('close'),
    })

    return () =>
      createElement('form', { 'data-testid': 'test-form', onKeydown: withKeys(maybeClose, ['esc']) }, [
        createElement('input', {
          name: 'name',
          value: data.name,
          onInput: (event: Event) => (data.name = (event.target as HTMLInputElement).value),
        }),
      ])
  },
})

describe('modalWrapper.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      modalOptions.value = { component: null }
      mockShowConfirmDialog.mockReset()
    },
  })

  const renderFormModal = async () => {
    const { container } = h.render(Component)

    modalOptions.value = { component: markRaw(TestForm), props: {} }

    await waitFor(() => screen.getByTestId('test-form'))

    return container.querySelector('dialog')!
  }

  it('shows a modal component', async () => {
    h.render(Component)

    modalOptions.value = {
      component: markRaw(h.stub('test-modal')),
      props: {},
    }

    await waitFor(() => screen.getByTestId('test-modal'))
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

  it('closes a pristine form when the dialog is canceled with focus outside the form', async () => {
    const dialog = await renderFormModal()

    await fireEvent(dialog, new Event('cancel', { cancelable: true }))

    await waitFor(() => expect(screen.queryByTestId('test-form')).toBeNull())
    expect(mockShowConfirmDialog).not.toHaveBeenCalled()
  })

  it('asks before discarding a dirty form when the dialog is canceled with focus outside the form', async () => {
    mockShowConfirmDialog.mockResolvedValueOnce(false)
    const dialog = await renderFormModal()

    await fireEvent.update(screen.getByRole('textbox'), 'Changed')
    await fireEvent(dialog, new Event('cancel', { cancelable: true }))

    await waitFor(() => expect(mockShowConfirmDialog).toHaveBeenCalledOnce())
    screen.getByTestId('test-form')
  })

  it('closes a pristine form on Escape when the dialog itself has focus', async () => {
    const dialog = await renderFormModal()

    await fireEvent.keyDown(dialog, { key: 'Escape' })

    await waitFor(() => expect(screen.queryByTestId('test-form')).toBeNull())
  })

  it('asks only once before discarding a dirty form on Escape from inside the form', async () => {
    mockShowConfirmDialog.mockResolvedValueOnce(false)
    await renderFormModal()

    const input = screen.getByRole('textbox')
    await fireEvent.update(input, 'Changed')
    await fireEvent.keyDown(input, { key: 'Escape' })

    await waitFor(() => expect(mockShowConfirmDialog).toHaveBeenCalledOnce())
  })

  it('resets the modal when the dialog is closed natively', async () => {
    const dialog = await renderFormModal()

    dialog.open = false
    await fireEvent(dialog, new Event('close'))

    await waitFor(() => expect(screen.queryByTestId('test-form')).toBeNull())
  })
})
