import { isEqual, omit } from 'lodash-es'
import { onMounted, ref, toRaw } from 'vue'
import { isHttpError } from '@/services/http'
import { useDialogBox } from '@/composables/useDialogBox'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useForm } from '@/composables/useForm'

interface AlbumOrArtistEditFormConfig<T extends Record<string, any> & { description?: string }> {
  initialValues: T
  artworkField: keyof T
  fetchOnlineDescription: () => Promise<string>
  onSubmit: (data: T) => Promise<unknown>
  mergeConfirmation: string
  onMerge: (data: T) => Promise<unknown>
  successMessage: string
  onSuccess: () => void
  onClose: () => void
}

class MergeDeclinedError extends Error {}

export const useAlbumOrArtistEditForm = <T extends Record<string, any> & { description?: string }>(
  config: AlbumOrArtistEditFormConfig<T>,
) => {
  const originalArtwork = config.initialValues[config.artworkField]
  const onlineDescription = ref('')
  const { showConfirmDialog } = useDialogBox()

  const isNameConflict = (error: unknown) => isHttpError(error) && error.response?.status === 409

  const form = useForm<T>({
    initialValues: config.initialValues,
    toast: {
      loading: 'Saving…',
      success: config.successMessage,
    },
    isPristine: (original, current) =>
      isEqual(omit(original, 'description'), omit(current, 'description')) &&
      (current.description === original.description ||
        (!original.description && current.description === onlineDescription.value)),
    onSubmit: async data => {
      const formData = structuredClone(toRaw(data)) as T

      if (formData.description === onlineDescription.value) {
        formData.description = ''
      }

      if (formData[config.artworkField] === originalArtwork) {
        delete formData[config.artworkField]
      }

      try {
        await config.onSubmit(formData)
      } catch (error: unknown) {
        if (!isNameConflict(error)) {
          throw error
        }

        if (!(await showConfirmDialog(config.mergeConfirmation))) {
          throw new MergeDeclinedError()
        }

        await config.onMerge(formData)
      }
    },
    onSuccess: config.onSuccess,
    onError: error => error instanceof MergeDeclinedError || useErrorHandler().handleHttpError(error),
    onClose: config.onClose,
  })

  onMounted(async () => {
    if (form.data.description) {
      return
    }

    onlineDescription.value = await config.fetchOnlineDescription()
    form.data.description ||= onlineDescription.value
  })

  return form
}
