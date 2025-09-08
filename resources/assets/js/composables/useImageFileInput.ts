import { useFileReader } from '@/composables/useFileReader'

export const useImageFileInput = (config: {
  onImageDataUrl: (dataUrl: string) => void
}) => {
  const { readAsDataUrl } = useFileReader()

  const onImageInputChange = (e: InputEvent) => {
    const target = e.target as HTMLInputElement

    if (!target.files || !target.files.length) {
      return
    }

    readAsDataUrl(target.files[0], dataUrl => config.onImageDataUrl(dataUrl))

    // reset the value so that, if the user removes the logo, they can re-pick the same one
    target.value = ''
  }

  return {
    onImageInputChange,
  }
}
