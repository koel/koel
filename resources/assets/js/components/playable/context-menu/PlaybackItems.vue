<template>
  <template v-if="playables.length === 1">
    <MenuItem @click="doPlayback">{{ playable.playback_state === 'Playing' ? 'Pause' : 'Play' }}</MenuItem>
    <Separator />
    <MenuItem>
      Go to
      <template #subMenuItems>
        <template v-if="isSong(playable)">
          <MenuItem :title="playable.album_name" @click="viewAlbum(playable)">
            <template #icon>
              <Icon :icon="faCompactDisc" fixed-width />
            </template>
            {{ playable.album_name }}
          </MenuItem>
          <MenuItem :title="playable.artist_name" @click="viewArtist(playable)">
            <template #icon>
              <MicVocalIcon :size="16" class="inline-block" />
            </template>
            {{ playable.artist_name }}
          </MenuItem>
        </template>
        <template v-else>
          <MenuItem @click="viewPodcast(playable as Episode)">
            <template #icon>
              <Icon :icon="faPodcast" fixed-width />
            </template>
            Podcast
          </MenuItem>
          <MenuItem @click="viewEpisode(playable as Episode)">
            <template #icon>
              <Icon :icon="faHeadphones" fixed-width />
            </template>
            Episode
          </MenuItem>
          <MenuItem v-if="(playable as Episode).episode_link" @click="visitEpisodeWebpage(playable as Episode)">
            <template #icon>
              <Icon :icon="faExternalLink" fixed-width />
            </template>
            Webpage
          </MenuItem>
        </template>
      </template>
    </MenuItem>
  </template>
</template>

<script lang="ts" setup>
import { faCompactDisc, faExternalLink, faHeadphones, faPodcast } from '@fortawesome/free-solid-svg-icons'
import { MicVocalIcon } from 'lucide-vue-next'
import { computed } from 'vue'
import { isSong } from '@/utils/typeGuards'
import { useContextMenu } from '@/composables/useContextMenu'
import { useRouter } from '@/composables/useRouter'
import { playback } from '@/services/playbackManager'

const props = defineProps<{ playables: Playable[] }>()

const { MenuItem, Separator, trigger } = useContextMenu()
const { go, url } = useRouter()

const playable = computed(() => props.playables[0])

const doPlayback = () =>
  trigger(async () => {
    switch (playable.value.playback_state) {
      case 'Playing':
        await playback().pause()
        break

      case 'Paused':
        await playback().resume()
        break

      default:
        await playback().play(playable.value)
        break
    }
  })

const viewAlbum = (song: Song) => trigger(() => go(url('albums.show', { id: song.album_id })))
const viewArtist = (song: Song) => trigger(() => go(url('artists.show', { id: song.artist_id })))
const viewPodcast = (episode: Episode) => trigger(() => go(url('podcasts.show', { id: episode.podcast_id })))
const viewEpisode = (episode: Episode) => trigger(() => go(url('episodes.show', { id: episode.id })))
const visitEpisodeWebpage = (episode: Episode) => trigger(() => window.open(episode.episode_link!, '_blank'))
</script>
