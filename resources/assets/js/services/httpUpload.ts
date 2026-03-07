import { authService } from '@/services/authService'

/**
 * Upload with progress tracking using XHR, since fetch/ky don't support upload progress.
 */
export const postWithProgress = <T>(url: string, data: FormData, onUploadProgress: (e: ProgressEvent) => void) => {
  return new Promise<T>((resolve, reject) => {
    const xhr = new XMLHttpRequest()
    xhr.open('POST', `${window.BASE_URL}api/${url}`)
    xhr.setRequestHeader('Authorization', `Bearer ${authService.getApiToken()}`)
    xhr.setRequestHeader('X-Api-Version', 'v7')

    xhr.upload.addEventListener('progress', onUploadProgress)

    xhr.addEventListener('load', () => {
      let responseData: any

      try {
        responseData = JSON.parse(xhr.responseText)
      } catch {
        responseData = xhr.responseText
      }

      if (xhr.status >= 200 && xhr.status < 300) {
        resolve(responseData)
      } else {
        const error = Object.assign(new Error(`Upload failed with status ${xhr.status}`), {
          responseData,
          status: xhr.status,
          statusText: xhr.statusText,
        })

        reject(error)
      }
    })

    xhr.addEventListener('error', () => reject(new Error('Network error')))

    xhr.send(data)
  })
}
