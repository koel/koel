export type UploadStatus =
  | 'Ready'
  | 'Uploading'
  | 'Uploaded'
  | 'Canceled'
  | 'Errored'

export interface UploadFile {
  id: string
  file: File
  status: UploadStatus
  name: string
  progress: number
  message?: string
}
