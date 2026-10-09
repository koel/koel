import { http } from '@/services/http'
import type { ListeningPeriod } from '@/utils/listeningStatistics'

export const listeningStatisticsService = {
  fetch: async (period: ListeningPeriod) =>
    await http.get<ListeningStatistics>(`me/listening-statistics?period=${period}`),
}
