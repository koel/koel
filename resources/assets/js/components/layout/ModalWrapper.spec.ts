import { describe, expect, it, vi } from 'vite-plus/test'
import { defineComponent, h as createElement, markRaw, shallowRef, withKeys } from 'vue'
import { fireEvent, screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useForm } from '@/composables/useForm'
import { useLocalStorage } from '@/composables/useLocalStorage'
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

const screenSize = vi.hoisted(() => ({ isDesktop: { value: true } }))

vi.mock('@vueuse/core', async importOriginal => {
  const { ref } = await import('vue')
  screenSize.isDesktop = ref(true)

  return {
    ...(await importOriginal<typeof import('@vueuse/core')>()),
    useMediaQuery: () => screenSize.isDesktop,
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

const NamedForm = defineComponent({
  setup: () => () => createElement('form', { name: 'test', 'data-testid': 'named-form' }),
})

describe('modalWrapper.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      modalOptions.value = { component: null }
      mockShowConfirmDialog.mockReset()
      screenSize.isDesktop.value = true
      localStorage.clear()

      vi.spyOn(HTMLElement.prototype, 'getBoundingClientRect').mockImplementation(function (this: HTMLElement) {
        return {
          width: Number.parseFloat(this.style.width) || 500,
          height: Number.parseFloat(this.style.minHeight) || 400,
        } as DOMRect
      })

      HTMLElement.prototype.setPointerCapture = vi.fn()
    },
    afterEach: () => vi.restoreAllMocks(),
  })

  const renderNamedForm = async () => {
    const { container } = h.render(Component)

    modalOptions.value = { component: markRaw(NamedForm), props: {} }

    await waitFor(() => screen.getByTestId('named-form'))

    return container.querySelector('dialog')!
  }

  const renderFormModal = async () => {
    const { container } = h.render(Component)

    modalOptions.value = { component: markRaw(TestForm), props: {} }

    await waitFor(() => screen.getByTestId('test-form'))

    return container.querySelector('dialog')!
  }

  it('resizes a named form from its corner, never below its starting size, and saves the size', async () => {
    const dialog = await renderNamedForm()
    const grip = await screen.findByTestId('modal-resize-grip')

    await fireEvent(grip, new MouseEvent('pointerdown', { clientX: 0, clientY: 0 }))
    await fireEvent(grip, new MouseEvent('pointermove', { clientX: -300, clientY: -300 }))
    expect([dialog.style.width, dialog.style.minHeight]).toEqual(['500px', '400px'])

    await fireEvent(grip, new MouseEvent('pointermove', { clientX: 50, clientY: 30 }))
    expect([dialog.style.width, dialog.style.minHeight]).toEqual(['600px', '460px'])

    await fireEvent(grip, new Event('lostpointercapture'))
    expect(useLocalStorage().get('modal-size.test')).toEqual({ width: 600, height: 460 })
  })

  it('keeps a resized form inside the viewport', async () => {
    const dialog = await renderNamedForm()
    const grip = await screen.findByTestId('modal-resize-grip')

    await fireEvent(grip, new MouseEvent('pointerdown', { clientX: 0, clientY: 0 }))
    await fireEvent(grip, new MouseEvent('pointermove', { clientX: 5000, clientY: 5000 }))

    expect([dialog.style.width, dialog.style.minHeight]).toEqual([`${innerWidth - 32}px`, `${innerHeight - 32}px`])
  })

  it('stops and resumes resizing as the window crosses the desktop width', async () => {
    const dialog = await renderNamedForm()
    await screen.findByTestId('modal-resize-grip')

    screenSize.isDesktop.value = false
    await waitFor(() => expect(screen.queryByTestId('modal-resize-grip')).toBeNull())
    expect(dialog.style.width).toBe('')

    screenSize.isDesktop.value = true
    await screen.findByTestId('modal-resize-grip')
    expect(dialog.style.width).toBe('500px')
  })

  it('opens a named form at its saved size', async () => {
    useLocalStorage().set('modal-size.test', { width: 640, height: 480 })

    const dialog = await renderNamedForm()

    await waitFor(() => expect([dialog.style.width, dialog.style.minHeight]).toEqual(['640px', '480px']))
  })

  it('fits a saved size into a shorter window and restores it when the window grows back', async () => {
    useLocalStorage().set('modal-size.test', { width: 640, height: 700 })
    const dialog = await renderNamedForm()
    await waitFor(() => expect(dialog.style.minHeight).toBe('700px'))

    vi.stubGlobal('innerHeight', 500)
    window.dispatchEvent(new Event('resize'))
    expect(dialog.style.minHeight).toBe('468px')

    vi.unstubAllGlobals()
    window.dispatchEvent(new Event('resize'))
    expect(dialog.style.minHeight).toBe('700px')
  })

  it('keeps an unnamed form at its own size', async () => {
    await renderFormModal()

    expect(screen.queryByTestId('modal-resize-grip')).toBeNull()
  })

  it('keeps forms at their own size on small screens', async () => {
    screenSize.isDesktop.value = false

    await renderNamedForm()

    expect(screen.queryByTestId('modal-resize-grip')).toBeNull()
  })

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

  it('closes a pristine form on Escape from a toast inside the dialog', async () => {
    const dialog = await renderFormModal()
    const toastButton = dialog.appendChild(document.createElement('button'))

    await fireEvent.keyDown(toastButton, { key: 'Escape' })

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

  it('closes an open dialog when unmounted', async () => {
    const { container, unmount } = h.render(Component)
    const dialog = container.querySelector('dialog')!
    const close = vi.spyOn(dialog, 'close')

    modalOptions.value = { component: markRaw(TestForm), props: {} }
    await waitFor(() => screen.getByTestId('test-form'))

    unmount()

    expect(close).toHaveBeenCalled()
  })
})
