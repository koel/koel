import { cache } from '@/services/cache'
import { http } from '@/services/http'
import { eventBus } from '@/utils/eventBus'

interface YouTubeSearchResult {
  nextPageToken: string
  items: YouTubeVideo[]
}

export const youTubeService = {
  searchVideosBySong: async (song: Song, nextPageToken: string) => {
    return await cache.remember<YouTubeSearchResult>(
      ['youtube.search', song.id, nextPageToken],
      async () => await http.get<YouTubeSearchResult>(
        `youtube/search/song/${song.id}?pageToken=${nextPageToken}`,
      ),
    )
  },

  play: (video: YouTubeVideo): void => {
    eventBus.emit('PLAY_YOUTUBE_VIDEO', {
      id: video.id.videoId,
      title: video.snippet.title,
    })
  },
}
