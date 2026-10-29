import { beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { useForm } from './useForm'

const mockShowConfirmDialog = vi.fn()
const mockHandleHttpError = vi.fn()
const mockToastLoading = vi.fn().mockReturnValue('toast-id')
const mockUpdateToast = vi.fn()
const mockDismissToast = vi.fn()

vi.mock('@/composables/useMessageToaster', () => ({
  useMessageToaster: () => ({
    toastLoading: mockToastLoading,
    updateToast: mockUpdateToast,
    dismissToast: mockDismissToast,
  }),
}))

vi.mock('@/composables/useErrorHandler', () => ({
  getHttpErrorMessage: () => 'Server error',
  useErrorHandler: () => ({
    handleHttpError: mockHandleHttpError,
  }),
}))

vi.mock('@/composables/useDialogBox', () => ({
  useDialogBox: () => ({
    showConfirmDialog: mockShowConfirmDialog,
  }),
}))

describe('useForm', () => {
  beforeEach(() => vi.clearAllMocks())

  it('detects pristine state', () => {
    const { isPristine, isDirty } = useForm({
      initialValues: { name: 'John' },
      onSubmit: vi.fn(),
    })

    expect(isPristine()).toBe(true)
    expect(isDirty()).toBe(false)
  })

  it('detects dirty state after mutation', () => {
    const { data, isPristine, isDirty } = useForm({
      initialValues: { name: 'John' },
      onSubmit: vi.fn(),
    })

    data.name = 'Jane'

    expect(isPristine()).toBe(false)
    expect(isDirty()).toBe(true)
  })

  it('uses custom isPristine function', () => {
    const { isPristine } = useForm({
      initialValues: { name: 'John' },
      onSubmit: vi.fn(),
      isPristine: () => true, // always pristine
    })

    expect(isPristine()).toBe(true)
  })

  it('calls onSuccess after successful submit', async () => {
    const onSuccess = vi.fn()
    const { handleSubmit } = useForm({
      initialValues: { name: 'John' },
      onSubmit: vi.fn().mockResolvedValue('result'),
      onSuccess,
    })

    await handleSubmit()

    expect(onSuccess).toHaveBeenCalledWith('result')
  })

  it('manages loading state during submit', async () => {
    let loadingDuringSubmit = false

    const { handleSubmit, loading } = useForm({
      initialValues: { name: 'John' },
      onSubmit: async () => {
        loadingDuringSubmit = loading.value
      },
    })

    expect(loading.value).toBe(false)
    await handleSubmit()
    expect(loadingDuringSubmit).toBe(true)
    expect(loading.value).toBe(false)
  })

  it('shows a loading toast while submitting and turns it into the success message', async () => {
    let toastShownDuringSubmit = false

    const { handleSubmit } = useForm({
      initialValues: { name: 'John' },
      toast: {
        loading: 'Saving',
        success: (result: string) => `Saved ${result}`,
      },
      onSubmit: async () => {
        toastShownDuringSubmit = mockToastLoading.mock.calls.length > 0
        return 'John'
      },
    })

    await handleSubmit()

    expect(toastShownDuringSubmit).toBe(true)
    expect(mockToastLoading).toHaveBeenCalledWith('Saving')
    expect(mockUpdateToast).toHaveBeenCalledWith('toast-id', {
      type: 'success',
      content: 'Saved John',
    })
  })

  it('closes the loading toast on success when there is no success message', async () => {
    const { handleSubmit } = useForm({
      initialValues: { name: 'John' },
      toast: {
        loading: 'Logging in',
      },
      onSubmit: vi.fn().mockResolvedValue(undefined),
    })

    await handleSubmit()

    expect(mockDismissToast).toHaveBeenCalledWith('toast-id')
  })

  it('turns the loading toast into the error and keeps the form open on failure', async () => {
    const onSuccess = vi.fn()

    const { data, handleSubmit } = useForm({
      initialValues: { name: 'John' },
      toast: {
        loading: 'Saving',
        success: 'Saved',
      },
      onSubmit: vi.fn().mockRejectedValue(new Error('fail')),
      onSuccess,
    })

    data.name = 'Jane'
    await handleSubmit()

    expect(mockUpdateToast).toHaveBeenCalledWith('toast-id', {
      type: 'danger',
      content: 'Server error',
    })
    expect(mockHandleHttpError).not.toHaveBeenCalled()
    expect(onSuccess).not.toHaveBeenCalled()
    expect(data.name).toBe('Jane')
  })

  it('closes the loading toast and leaves the error to a custom error handler', async () => {
    const error = new Error('fail')
    const onError = vi.fn()

    const { handleSubmit } = useForm({
      initialValues: { name: 'John' },
      toast: {
        loading: 'Saving',
      },
      onSubmit: vi.fn().mockRejectedValue(error),
      onError,
    })

    await handleSubmit()

    expect(mockDismissToast).toHaveBeenCalledWith('toast-id')
    expect(mockUpdateToast).not.toHaveBeenCalled()
    expect(onError).toHaveBeenCalledWith(error)
  })

  it('shows no toast and reports errors the usual way without a toast config', async () => {
    const { handleSubmit } = useForm({
      initialValues: { name: 'John' },
      onSubmit: vi.fn().mockRejectedValue(new Error('fail')),
    })

    await handleSubmit()

    expect(mockToastLoading).not.toHaveBeenCalled()
    expect(mockHandleHttpError).toHaveBeenCalled()
  })

  it('ignores a second submit while the first one is pending', async () => {
    let finishSubmitting!: () => void
    const onSubmit = vi.fn(() => new Promise<void>(resolve => (finishSubmitting = resolve)))

    const { handleSubmit } = useForm({
      initialValues: { name: 'John' },
      onSubmit,
    })

    const firstSubmit = handleSubmit()
    await handleSubmit()
    finishSubmitting()
    await firstSubmit

    expect(onSubmit).toHaveBeenCalledOnce()
  })

  it('aborts submission when validator returns false', async () => {
    const onSubmit = vi.fn()
    const { handleSubmit } = useForm({
      initialValues: { name: '' },
      onSubmit,
      validator: () => false,
    })

    await handleSubmit()

    expect(onSubmit).not.toHaveBeenCalled()
  })

  it('proceeds with submission when validator returns true', async () => {
    const onSubmit = vi.fn().mockResolvedValue(undefined)
    const { handleSubmit } = useForm({
      initialValues: { name: 'John' },
      onSubmit,
      validator: () => true,
    })

    await handleSubmit()

    expect(onSubmit).toHaveBeenCalled()
  })

  it('calls onError on submission failure', async () => {
    const error = new Error('fail')
    const onError = vi.fn()
    const { handleSubmit } = useForm({
      initialValues: { name: 'John' },
      onSubmit: vi.fn().mockRejectedValue(error),
      onError,
    })

    await handleSubmit()

    expect(onError).toHaveBeenCalledWith(error)
  })

  it('calls onFinally after submit regardless of outcome', async () => {
    const onFinally = vi.fn()
    const { handleSubmit } = useForm({
      initialValues: { name: 'John' },
      onSubmit: vi.fn().mockRejectedValue(new Error('fail')),
      onError: vi.fn(),
      onFinally,
    })

    await handleSubmit()

    expect(onFinally).toHaveBeenCalled()
  })

  it('closes a pristine form without confirmation', async () => {
    mockShowConfirmDialog.mockClear()
    const onClose = vi.fn()

    const { maybeClose } = useForm({
      initialValues: { name: 'John' },
      onSubmit: vi.fn(),
      onClose,
    })

    await maybeClose()

    expect(mockShowConfirmDialog).not.toHaveBeenCalled()
    expect(onClose).toHaveBeenCalled()
  })

  it('closes a dirty form once discarding is confirmed', async () => {
    mockShowConfirmDialog.mockResolvedValue(true)
    const onClose = vi.fn()

    const { data, maybeClose } = useForm({
      initialValues: { name: 'John' },
      onSubmit: vi.fn(),
      onClose,
    })

    data.name = 'Jane'
    await maybeClose()

    expect(mockShowConfirmDialog).toHaveBeenCalledWith('Discard all changes?')
    expect(onClose).toHaveBeenCalled()
  })

  it('keeps a dirty form open when discarding is declined', async () => {
    mockShowConfirmDialog.mockResolvedValue(false)
    const onClose = vi.fn()

    const { data, maybeClose } = useForm({
      initialValues: { name: 'John' },
      onSubmit: vi.fn(),
      onClose,
    })

    data.name = 'Jane'
    await maybeClose()

    expect(onClose).not.toHaveBeenCalled()
  })

  it('asks for discarding with a custom confirmation', async () => {
    mockShowConfirmDialog.mockResolvedValue(false)

    const { data, maybeClose } = useForm({
      initialValues: { name: 'John' },
      onSubmit: vi.fn(),
      onClose: vi.fn(),
      discardConfirmation: 'Discard this passkey?',
    })

    data.name = 'Jane'
    await maybeClose()

    expect(mockShowConfirmDialog).toHaveBeenCalledWith('Discard this passkey?')
  })
})
