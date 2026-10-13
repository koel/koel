import { isEqual, omit } from 'lodash-es'
import { onMounted, ref, toRaw } from 'vue'
import { useForm } from '@/composables/useForm'

interface AlbumOrArtistEditFormConfig<T extends Record<string, any> & { description?: string }> {
  initialValues: T
  artworkField: keyof T
  fetchOnlineDescription: () => Promise<string>
  onSubmit: (data: T) => Promise<void>
  onSuccess: () => void
  onClose: () => void
}

export const useAlbumOrArtistEditForm = <T extends Record<string, any> & { description?: string }>(
  config: AlbumOrArtistEditFormConfig<T>,
) => {
  const originalArtwork = config.initialValues[config.artworkField]
  const onlineDescription = ref('')

  const form = useForm<T>({
    initialValues: config.initialValues,
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

      await config.onSubmit(formData)
    },
    onSuccess: config.onSuccess,
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
