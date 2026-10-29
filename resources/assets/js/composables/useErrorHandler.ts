import { getHttpErrorBody, isHttpError } from '@/services/http'
import { logger } from '@/utils/logger'
import { parseValidationError } from '@/utils/formatters'
import type { ServerValidationError } from '@/utils/formatters'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'

export interface StatusMessageMap {
  [key: number]: string | Closure
}

type ErrorMessageDriver = 'toast' | 'dialog'

const GENERIC_ERROR_MESSAGE = 'An unknown error occurred.'

export const getHttpErrorMessage = (error: unknown) => {
  if (!isHttpError(error) || !error.response?.status) {
    return GENERIC_ERROR_MESSAGE
  }

  const data = getHttpErrorBody(error)

  if (error.response.status === 422) {
    return parseValidationError(data as ServerValidationError)[0]
  }

  return data?.message || GENERIC_ERROR_MESSAGE
}

export const useErrorHandler = (driver: ErrorMessageDriver = 'toast') => {
  const { toastError } = useMessageToaster()
  const { showErrorDialog } = useDialogBox()

  const showError = (message: string) => (driver === 'toast' ? toastError(message) : showErrorDialog(message))

  const showGenericError = () => showError(GENERIC_ERROR_MESSAGE)

  const handleHttpError = (error: unknown, statusMessageMap: StatusMessageMap = {}) => {
    logger.error(error)

    const status = isHttpError(error) ? error.response?.status : undefined
    const messageOrClosure = status ? statusMessageMap[status] : undefined

    if (messageOrClosure) {
      return typeof messageOrClosure === 'string' ? showError(messageOrClosure) : messageOrClosure()
    }

    return showError(getHttpErrorMessage(error))
  }

  return {
    handleHttpError,
    showGenericError,
  }
}
