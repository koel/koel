import { http } from '@/services/http'

export const genreStore = {
  fetchAll: async () => await http.get<Genre[]>('genres'),
  fetchOne: async (id: Genre['id']) => await http.get<Genre>(`genres/${id}`),
}
