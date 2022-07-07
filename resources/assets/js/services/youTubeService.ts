import { Cache, httpService } from '@/services'
import { eventBus } from '@/utils'
import router from '@/router'

interface YouTubeSearchResult {
  nextPageToken: string
  items: Record<string, any>[]
}

export const youTubeService = {
  searchVideosBySong: async (song: Song, nextPageToken: string) => {
    return await Cache.resolve<YouTubeSearchResult>(
      ['youtube.search', song.id, nextPageToken],
      async () => await httpService.get<YouTubeSearchResult>(
        `youtube/search/song/${song.id}?pageToken=${nextPageToken}`
      )
    )
  },

  play: (video: YouTubeVideo): void => {
    eventBus.emit('PLAY_YOUTUBE_VIDEO', {
      id: video.id.videoId,
      title: video.snippet.title
    })

    router.go('youtube')
  }
}
