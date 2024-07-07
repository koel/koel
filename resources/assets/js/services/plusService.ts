import { http } from '@/services'

export const plusService = {
  activateLicense: async (key: string) => await http.post('licenses/activate', { key })
}
