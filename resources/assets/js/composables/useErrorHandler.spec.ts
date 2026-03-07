import { describe, expect, it, vi } from 'vitest'
import { HTTPError } from 'ky'
import { useErrorHandler } from './useErrorHandler'

const mockToastError = vi.fn()
const mockShowErrorDialog = vi.fn()

vi.mock('@/composables/useMessageToaster', () => ({
  useMessageToaster: () => ({
    toastError: mockToastError,
  }),
}))

vi.mock('@/composables/useDialogBox', () => ({
  useDialogBox: () => ({
    showErrorDialog: mockShowErrorDialog,
  }),
}))

vi.mock('@/utils/logger', () => ({
  logger: { error: vi.fn() },
}))

describe('useErrorHandler', () => {
  const createHttpError = (status: number, data: any = {}) => {
    const response = new Response(JSON.stringify(data), {
      status,
      statusText: 'Error',
      headers: { 'Content-Type': 'application/json' },
    })

    const request = new Request('http://test/api/test')
    const error = new HTTPError(response, request, {} as any)
    ;(error as any).responseData = data

    return error
  }

  it('shows generic error for non-HTTP errors', () => {
    const { handleHttpError } = useErrorHandler('toast')
    handleHttpError(new Error('something went wrong'))

    expect(mockToastError).toHaveBeenCalledWith('An unknown error occurred.')
  })

  it('uses toast driver by default', () => {
    const { handleHttpError } = useErrorHandler()
    handleHttpError(new Error('fail'))

    expect(mockToastError).toHaveBeenCalled()
    expect(mockShowErrorDialog).not.toHaveBeenCalled()
  })

  it('uses dialog driver when specified', () => {
    const { handleHttpError } = useErrorHandler('dialog')
    handleHttpError(new Error('fail'))

    expect(mockShowErrorDialog).toHaveBeenCalledWith('An unknown error occurred.')
  })

  it('shows validation error for 422 responses', () => {
    const { handleHttpError } = useErrorHandler('toast')
    const error = createHttpError(422, {
      errors: { name: ['The name field is required.'] },
    })

    handleHttpError(error)

    expect(mockToastError).toHaveBeenCalledWith('The name field is required.')
  })

  it('uses custom status message map', () => {
    const { handleHttpError } = useErrorHandler('toast')
    const error = createHttpError(403, {})

    handleHttpError(error, { 403: 'Access denied.' })

    expect(mockToastError).toHaveBeenCalledWith('Access denied.')
  })

  it('calls closure from status message map', () => {
    const closure = vi.fn()
    const { handleHttpError } = useErrorHandler('toast')
    const error = createHttpError(403, {})

    handleHttpError(error, { 403: closure })

    expect(closure).toHaveBeenCalled()
  })

  it('falls back to response message', () => {
    const { handleHttpError } = useErrorHandler('toast')
    const error = createHttpError(500, { message: 'Internal Server Error' })

    handleHttpError(error)

    expect(mockToastError).toHaveBeenCalledWith('Internal Server Error')
  })

  it('shows generic error when no message is available', () => {
    const { handleHttpError } = useErrorHandler('toast')
    const error = createHttpError(500, {})

    handleHttpError(error)

    expect(mockToastError).toHaveBeenCalledWith('An unknown error occurred.')
  })

  it('prefers custom 422 message over default validation parsing', () => {
    const { handleHttpError } = useErrorHandler('toast')
    const error = createHttpError(422, {
      errors: { name: ['The name field is required.'] },
    })

    handleHttpError(error, { 422: 'Custom validation message.' })

    expect(mockToastError).toHaveBeenCalledWith('Custom validation message.')
  })
})
