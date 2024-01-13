import { http } from '@/services'

export const plusService = {
  activateLicense: async (key: string) => {
    return await http.post('licenses/activate', { key })
  }
}
