import { useMessageToaster, useDialogBox } from '@/composables'
import axios, { AxiosResponse } from 'axios'
import { logger, parseValidationError } from '@/utils'

export interface StatusMessageMap {
  [key: AxiosResponse['status']]: string | Closure
}

type ErrorMessageDriver = 'toast' | 'dialog'

export const useErrorHandler = (driver: ErrorMessageDriver = 'toast') => {
  const { toastError } = useMessageToaster()
  const { showErrorDialog } = useDialogBox()

  const showGenericError = () => {
    if (driver === 'toast') {
      toastError('An unknown error occurred.')
    } else {
      showErrorDialog('An unknown error occurred.')
    }
  }

  const handleHttpError = (
    error: unknown,
    statusMessageMap: StatusMessageMap = {}
  ) => {
    logger.error(error)

    if (!axios.isAxiosError(error)) {
      return showGenericError()
    }

    if (!error.response?.status || !statusMessageMap.hasOwnProperty(error.response.status)) {
      showError('An unknown error occurred.')
      return
    }

    if (error.response?.status === 422) {
      return showError(parseValidationError(error.response.data)[0])
    }

    if (typeof statusMessageMap[error.response!.status!] === 'string') {
      showError(statusMessageMap[error.response!.status!]) // @ts-ignore
    } else {
      return statusMessageMap[error.response!.status!]() // @ts-ignore
    }
  }

  const showError = (message: string) => {
    if (driver === 'toast') {
      toastError(message)
    } else {
      showErrorDialog(message)
    }
  }

  return {
    handleHttpError,
    parseValidationError,
    showGenericError
  }
}
