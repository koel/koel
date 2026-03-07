import { isHttpError } from '@/services/http'
import { logger } from '@/utils/logger'
import { parseValidationError } from '@/utils/formatters'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'

export interface StatusMessageMap {
  [key: number]: string | Closure
}

type ErrorMessageDriver = 'toast' | 'dialog'

export const useErrorHandler = (driver: ErrorMessageDriver = 'toast') => {
  const { toastError } = useMessageToaster()
  const { showErrorDialog } = useDialogBox()

  const showError = (message: string) => (driver === 'toast' ? toastError(message) : showErrorDialog(message))

  const showGenericError = () => showError('An unknown error occurred.')

  const handleHttpError = (error: unknown, statusMessageMap: StatusMessageMap = {}) => {
    logger.error(error)

    if (!isHttpError(error) || !error.response?.status) {
      return showGenericError()
    }

    const status = error.response.status
    const data = (error as any).responseData

    if (!Object.prototype.hasOwnProperty.call(statusMessageMap, status) && status === 422) {
      return showError(parseValidationError(data)[0])
    }

    const messageOrClosure = statusMessageMap[status]

    if (messageOrClosure) {
      return typeof messageOrClosure === 'string' ? showError(messageOrClosure) : messageOrClosure()
    }

    if (data?.message) {
      return showError(data.message)
    }

    return showGenericError()
  }

  return {
    handleHttpError,
    showGenericError,
  }
}
