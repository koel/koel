import { http } from '@/services/http'
import type { ListeningPeriod } from '@/utils/listeningStatistics'

export const statisticsService = {
  fetchListeningStatistics: async (period: ListeningPeriod) => {
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone
    const query = new URLSearchParams({ period, timezone })

    return await http.get<ListeningStatistics>(`me/listening-statistics?${query}`)
  },

  fetchLibraryStatistics: async () => await http.get<LibraryStatistic[]>('me/library-statistics'),
}
