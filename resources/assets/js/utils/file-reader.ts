export const fileReader = {
  /**
   * Read a File into a binary string
   */
  readAsDataUrl: (file: Blob) => new Promise<string>((resolve, reject): void => {
    const reader = new FileReader()
    reader.readAsDataURL(file)
    reader.onload = () => resolve(<string>reader.result)
    reader.onerror = error => reject(error)
  })
}
