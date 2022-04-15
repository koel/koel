import { http } from '@/services'
import { eventBus } from '@/utils'
import router from '@/router'

interface YouTubeSearchResult {
  nextPageToken: string
  items: object[]
}

export const youtube = {
  searchVideosRelatedToSong: async (song: Song, nextPageToken: string): Promise<YouTubeSearchResult> => {
    return await http.get<YouTubeSearchResult>(`youtube/search/song/${song.id}?pageToken=${nextPageToken}`)
  },

  play: (video: YouTubeVideo): void => {
    eventBus.emit('PLAY_YOUTUBE_VIDEO', {
      id: video.id.videoId,
      title: video.snippet.title
    })

    router.go('youtube')
  }
}
