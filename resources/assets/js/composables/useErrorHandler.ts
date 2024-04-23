import axios, { AxiosResponse } from 'axios'
import { logger, parseValidationError } from '@/utils'
import { useDialogBox, useMessageToaster } from '@/composables'

export interface StatusMessageMap {
  [key: AxiosResponse['status']]: string | Closure
}

type ErrorMessageDriver = 'toast' | 'dialog'

export const useErrorHandler = (driver: ErrorMessageDriver = 'toast') => {
  const { toastError } = useMessageToaster()
  const { showErrorDialog } = useDialogBox()

  const showError = (message: string) => {
    if (driver === 'toast') {
      toastError(message)
    } else {
      showErrorDialog(message)
    }
  }

  const showGenericError = () => showError('An unknown error occurred.')

  const handleHttpError = (error: unknown, statusMessageMap: StatusMessageMap = {}) => {
    logger.error(error)

    if (!axios.isAxiosError(error)) {
      return showGenericError()
    }

    if (!error.response?.status || !Object.prototype.hasOwnProperty.call(statusMessageMap, error.response.status)) {
      showError('An unknown error occurred.')
      return
    }

    if (error.response.status === 422) {
      return showError(parseValidationError(error.response.data)[0])
    }

    const messageOrClosure = statusMessageMap[error.response.status]

    if (typeof messageOrClosure === 'string') {
      showError(messageOrClosure)
    } else {
      return messageOrClosure()
    }
  }

  return {
    handleHttpError,
    showGenericError
  }
}
