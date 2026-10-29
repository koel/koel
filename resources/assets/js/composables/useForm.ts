import type { Reactive } from 'vue'
import { reactive, ref, toRaw } from 'vue'
import { isEqual } from 'lodash-es'
import { useDialogBox } from '@/composables/useDialogBox'
import { getHttpErrorMessage, useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { logger } from '@/utils/logger'

type MaybePromise<T> = T | Promise<T>

interface UseFormConfig<T extends Record<string, any>> {
  initialValues: T
  onSubmit: (data: Reactive<T>) => Promise<any>
  onSuccess?: (result: any) => MaybePromise<any>
  onError?: (error: unknown) => MaybePromise<any>
  onFinally?: () => MaybePromise<any>
  validator?: (data: Reactive<T>) => MaybePromise<boolean>
  isPristine?: (originalData: T, currentData: Reactive<T>) => MaybePromise<boolean>
  toast?: {
    loading: string
    success?: string | ((result: any) => string)
  }
  onClose?: () => void
  discardConfirmation?: string
}

export const useForm = <T extends Record<string, any>>(config: UseFormConfig<T>) => {
  const { toastLoading, updateToast, dismissToast } = useMessageToaster()

  const loading = ref(false)
  const data = reactive<T>(config.initialValues)
  const rawOriginalData = structuredClone(toRaw(data)) as T

  const isPristine = () =>
    config.isPristine ? config.isPristine(rawOriginalData, toRaw(data)) : isEqual(rawOriginalData, toRaw(data))

  const confirmDiscard = config.onClose ? useDialogBox().showConfirmDialog : undefined

  const maybeClose = async () => {
    if ((await isPristine()) || (await confirmDiscard?.(config.discardConfirmation ?? 'Discard all changes?'))) {
      config.onClose?.()
    }
  }

  const settleToastAsSuccess = (toastId: string, result: unknown) => {
    const success = config.toast?.success
    const message = typeof success === 'function' ? success(result) : success

    if (!message) {
      dismissToast(toastId)
      return
    }

    updateToast(toastId, {
      type: 'success',
      content: message,
    })
  }

  const onError = async (error: unknown, toastId: string | null) => {
    if (config.onError) {
      toastId && dismissToast(toastId)
      await config.onError(error)
      return
    }

    if (toastId) {
      logger.error(error)
      updateToast(toastId, {
        type: 'danger',
        content: getHttpErrorMessage(error),
      })
      return
    }

    useErrorHandler('dialog').handleHttpError(error)
  }

  const handleSubmit = async () => {
    if (loading.value) {
      return
    }

    if (config.validator && !(await config.validator?.(data))) {
      return
    }

    loading.value = true
    const toastId = config.toast ? toastLoading(config.toast.loading) : null

    try {
      const result = await config.onSubmit(data)
      toastId && settleToastAsSuccess(toastId, result)
      await config.onSuccess?.(result)
    } catch (error: unknown) {
      await onError(error, toastId)
    } finally {
      await config.onFinally?.()
      loading.value = false
    }
  }

  return {
    data,
    loading,
    isPristine,
    isDirty: () => !isPristine(),
    handleSubmit,
    maybeClose,
  }
}
