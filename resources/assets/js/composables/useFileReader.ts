export const useFileReader = () => {
  const reader = new FileReader()

  const readAsDataUrl = (file: File, callback: (result: string) => void | Promise<void>) => {
    reader.addEventListener('load', async () => await callback(reader.result as string))
    reader.readAsDataURL(file)
  }

  return {
    readAsDataUrl
  }
}
