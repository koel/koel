import { describe, expect, it, vi } from 'vite-plus/test'
import { useForm } from './useForm'

const mockShowOverlay = vi.fn()
const mockHideOverlay = vi.fn()

vi.mock('@/composables/useOverlay', () => ({
  useOverlay: () => ({
    showOverlay: mockShowOverlay,
    hideOverlay: mockHideOverlay,
  }),
}))

vi.mock('@/composables/useErrorHandler', () => ({
  useErrorHandler: () => ({
    handleHttpError: vi.fn(),
  }),
}))

describe('useForm', () => {
  it('initializes with given values', () => {
    const { data } = useForm({
      initialValues: { name: 'John', age: 30 },
      onSubmit: vi.fn(),
    })

    expect(data.name).toBe('John')
    expect(data.age).toBe(30)
  })

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

  it('calls onSubmit on handleSubmit', async () => {
    const onSubmit = vi.fn().mockResolvedValue('result')
    const { handleSubmit } = useForm({
      initialValues: { name: 'John' },
      onSubmit,
    })

    await handleSubmit()

    expect(onSubmit).toHaveBeenCalled()
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

  it('shows and hides overlay by default', async () => {
    const { handleSubmit } = useForm({
      initialValues: { name: 'John' },
      onSubmit: vi.fn().mockResolvedValue(undefined),
    })

    await handleSubmit()

    expect(mockShowOverlay).toHaveBeenCalled()
    expect(mockHideOverlay).toHaveBeenCalled()
  })

  it('skips overlay when useOverlay is false', async () => {
    mockShowOverlay.mockClear()
    mockHideOverlay.mockClear()

    const { handleSubmit } = useForm({
      initialValues: { name: 'John' },
      onSubmit: vi.fn().mockResolvedValue(undefined),
      useOverlay: false,
    })

    await handleSubmit()

    expect(mockShowOverlay).not.toHaveBeenCalled()
    expect(mockHideOverlay).not.toHaveBeenCalled()
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
})
