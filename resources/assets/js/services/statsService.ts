import { http } from '@/services/http'
import type { ListeningPeriod } from '@/utils/listeningStatistics'

export const statsService = {
  fetchListeningStats: async (period: ListeningPeriod) => {
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone
    const query = new URLSearchParams({ period, timezone })

    return await http.get<ListeningStatistics>(`me/listening-stats?${query}`)
  },

  fetchLibraryStats: async () => await http.get<LibraryStat[]>('me/library-stats'),
}
