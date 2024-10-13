import type { AxiosResponse } from 'axios'
import axios from 'axios'
import { logger, parseValidationError } from '@/utils'
import { useDialogBox, useMessageToaster } from '@/composables'

export interface StatusMessageMap {
  [key: AxiosResponse['status']]: string | Closure
}

type ErrorMessageDriver = 'toast' | 'dialog'

export const useErrorHandler = (driver: ErrorMessageDriver = 'toast') => {
  const { toastError } = useMessageToaster()
  const { showErrorDialog } = useDialogBox()

  const showError = (message: string) => driver === 'toast' ? toastError(message) : showErrorDialog(message)

  const showGenericError = () => showError('An unknown error occurred.')

  const handleHttpError = (error: unknown, statusMessageMap: StatusMessageMap = {}) => {
    logger.error(error)

    if (!axios.isAxiosError(error) || !error.response?.status) {
      return showGenericError()
    }

    if (
      !Object.prototype.hasOwnProperty.call(statusMessageMap, error.response.status)
      && error.response.status === 422
    ) {
      return showError(parseValidationError(error.response.data)[0])
    }

    const messageOrClosure = statusMessageMap[error.response.status]

    if (messageOrClosure) {
      return typeof messageOrClosure === 'string' ? showError(messageOrClosure) : messageOrClosure()
    }

    if (error.response.data.message) {
      return showError(error.response.data.message)
    }

    return showGenericError()
  }

  return {
    handleHttpError,
    showGenericError,
  }
}
