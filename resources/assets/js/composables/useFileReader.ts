export const useFileReader = () => {
  const readAsDataUrl = (file: File, callback: (result: string) => void | Promise<void>) => {
    const reader = new FileReader()
    reader.addEventListener('load', async () => await callback(reader.result as string), { once: true })
    reader.readAsDataURL(file)
  }

  return {
    readAsDataUrl,
  }
}
