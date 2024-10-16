import { http } from '@/services/http'

export const plusService = {
  activateLicense: async (key: string) => await http.post('licenses/activate', { key }),
}
