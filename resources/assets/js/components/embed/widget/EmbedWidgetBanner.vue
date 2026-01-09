<template>
  <header class="flex h-[150px] p-6 gap-5 bg-k-fg-5 flex-shrink-0 justify-end">
    <aside class="size-[112px] aspect-square">
      <Thumbnail :embeddable="embed.embeddable" />
    </aside>

    <div class="flex-1 flex flex-col justify-end gap-3">
      <h3
        :title="heading"
        class="text-3xl text-k-fg flex items-center gap-2 font-medium sm:text-4xl sm:font-bold line-clamp-1"
      >
        <PreviewBadge v-if="options.preview" />
        <span>{{ heading }}</span>
      </h3>

      <p :title="subheading" class="line-clamp-1">{{ subheading }}</p>

      <slot name="audio-player" />
    </div>

    <span class="absolute right-3 top-3 size-10 p-1 bg-k-fg-10 rounded-md">
      <img :src="logo" alt="Koel's logo">
    </span>
  </header>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { getPlayableProp } from '@/utils/helpers'
import { isSong } from '@/utils/typeGuards'
import { useBranding } from '@/composables/useBranding'
import { artistStore } from '@/stores/artistStore'

import Thumbnail from '@/components/embed/widget/EmbedWidgetThumbnail.vue'
import PreviewBadge from '@/components/embed/widget/PreviewBadge.vue'

const props = defineProps<{ embed: WidgetReadyEmbed, options: EmbedOptions }>()
const { embed, options } = props

const { t } = useI18n()
const { logo } = useBranding()

let heading: string
let subheading: string

const setAttributes = (_heading: string, _subheading: string) => {
  heading = _heading
  subheading = _subheading
}

switch (embed.embeddable_type) {
  case 'album':
    const album = embed.embeddable as Album
    const albumArtistName = artistStore.isUnknown(album.artist_name) ? t('screens.unknownArtist') : album.artist_name
    setAttributes(album.name, albumArtistName)
    break
  case 'artist':
    setAttributes((embed.embeddable as Artist).name, t('artists.artist'))
    break
  case 'playable':
    const playable = embed.embeddable as Playable
    let playableArtistName = getPlayableProp(playable, 'artist_name', 'podcast_title')
    if (isSong(playable) && artistStore.isUnknown(playableArtistName)) {
      playableArtistName = t('screens.unknownArtist')
    }
    setAttributes(
      getPlayableProp(playable, 'title', 'title'),
      playableArtistName,
    )
    break
  case 'playlist':
    const playlist = embed.embeddable as Playlist
    setAttributes(playlist.name, playlist.description || t('playlists.playlist'))
    break
}
</script>
