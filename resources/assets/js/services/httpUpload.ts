import { authService } from '@/services/authService'

export interface UploadHandle<T> {
  promise: Promise<T>
  abort: () => void
}

/**
 * Upload with progress tracking using XHR, since fetch/ky don't support upload progress.
 */
export const postWithProgress = <T>(
  url: string,
  data: FormData,
  onUploadProgress: (e: ProgressEvent) => void,
): UploadHandle<T> => {
  const xhr = new XMLHttpRequest()

  const promise = new Promise<T>((resolve, reject) => {
    xhr.open('POST', `${window.BASE_URL}api/${url}`)
    xhr.setRequestHeader('Accept', 'application/json')
    xhr.setRequestHeader('Authorization', `Bearer ${authService.getApiToken()}`)
    xhr.setRequestHeader('X-Api-Version', 'v7')

    xhr.upload.addEventListener('progress', onUploadProgress)

    xhr.addEventListener('load', () => {
      let responseData: unknown

      try {
        responseData = JSON.parse(xhr.responseText)
      } catch {
        responseData = undefined
      }

      if (xhr.status >= 200 && xhr.status < 300) {
        resolve(responseData as T)
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
    xhr.addEventListener('abort', () => reject(new DOMException('Upload aborted', 'AbortError')))

    xhr.send(data)
  })

  return { promise, abort: () => xhr.abort() }
}
