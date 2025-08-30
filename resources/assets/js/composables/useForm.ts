import type { Reactive } from 'vue'
import { reactive, ref, toRaw } from 'vue'
import { cloneDeep, isEqual } from 'lodash'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useOverlay as useOverlayComposable } from '@/composables/useOverlay'

type MaybePromise<T> = T | Promise<T>

interface UseFormConfig<T extends Record<string, any>> {
  initialValues: T
  onSubmit: (data: Reactive<T>) => Promise<any>
  onSuccess?: (result: any) => MaybePromise<any>
  onError?: (error: unknown) => MaybePromise<any>
  onFinally?: () => MaybePromise<any>
  validator?: (data: Reactive<T>) => MaybePromise<boolean>
  isPristine?: (originalData: T, currentData: Reactive<T>) => MaybePromise<boolean>
  useOverlay?: boolean
}

export const useForm = <T extends Record<string, any>> (config: UseFormConfig<T>) => {
  const useOverlay = config.useOverlay ?? true
  const { showOverlay, hideOverlay } = useOverlayComposable()

  const loading = ref(false)
  const data = reactive<T>(config.initialValues)
  const rawOriginalData = cloneDeep(toRaw(data)) as T

  const isPristine = () => config.isPristine
    ? config.isPristine(rawOriginalData, toRaw(data))
    : isEqual(rawOriginalData, toRaw(data))

  const onError = async (error: unknown) => {
    config.onError
      ? await config.onError(error)
      : useErrorHandler('dialog').handleHttpError(error)
  }

  const handleSubmit = async () => {
    if (config.validator && !await config.validator?.(data)) {
      return
    }

    try {
      if (useOverlay) {
        showOverlay()
      }

      loading.value = true
      const result = await config.onSubmit(data)
      await config.onSuccess?.(result)
    } catch (error: unknown) {
      await onError(error)
    } finally {
      await config.onFinally?.()
      loading.value = false

      if (useOverlay) {
        hideOverlay()
      }
    }
  }

  return {
    data,
    loading,
    isPristine,
    isDirty: () => !isPristine(),
    handleSubmit,
  }
}
