import { authService } from '@/services/authService'

export interface UploadResponse<T> {
  status: number
  data: T
}

export interface UploadHandle<T> {
  promise: Promise<UploadResponse<T>>
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
  return sendWithProgress<T>('POST', `${window.KOEL.base_url}api/${url}`, data, onUploadProgress, {
    Accept: 'application/json',
    Authorization: `Bearer ${authService.getApiToken()}`,
    'X-Api-Version': 'v7',
  })
}

/**
 * Send a file straight to object storage with a presigned URL. Carries no Koel credentials: the
 * signature in the URL is the authorization, and sending a bearer token would break it.
 */
export const putToStorageWithProgress = (
  url: string,
  file: File,
  headers: Record<string, string>,
  onUploadProgress: (e: ProgressEvent) => void,
): UploadHandle<null> => {
  return sendWithProgress<null>('PUT', url, file, onUploadProgress, headers)
}

const sendWithProgress = <T>(
  method: 'POST' | 'PUT',
  url: string,
  body: FormData | File,
  onUploadProgress: (e: ProgressEvent) => void,
  headers: Record<string, string>,
): UploadHandle<T> => {
  const xhr = new XMLHttpRequest()

  const promise = new Promise<UploadResponse<T>>((resolve, reject) => {
    xhr.open(method, url)
    Object.entries(headers).forEach(([name, value]) => xhr.setRequestHeader(name, value))

    xhr.upload.addEventListener('progress', onUploadProgress)

    xhr.addEventListener('load', () => {
      let responseData: unknown

      try {
        responseData = JSON.parse(xhr.responseText)
      } catch {
        responseData = undefined
      }

      if (xhr.status >= 200 && xhr.status < 300) {
        resolve({ status: xhr.status, data: responseData as T })
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

    xhr.send(body)
  })

  return { promise, abort: () => xhr.abort() }
}
