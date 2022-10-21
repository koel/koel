import { http } from '@/services'

export const genreStore = {
  fetchAll: async () => await http.get<Genre[]>('genres'),
  fetchOne: async (name: string) => await http.get<Genre>(`genres/${name}`)
}
