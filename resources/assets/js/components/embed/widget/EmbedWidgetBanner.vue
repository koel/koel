<template>
  <header class="flex h-[150px] p-6 gap-5 bg-white/5 flex-shrink-0 justify-end">
    <aside class="size-[112px] aspect-square">
      <Thumbnail :embeddable="embed.embeddable" />
    </aside>

    <div class="flex-1 flex flex-col justify-end gap-3">
      <h3
        class="text-3xl flex items-center gap-2 font-medium sm:text-4xl sm:font-bold line-clamp-1"
        :title="heading"
      >
        <PreviewBadge v-if="options.preview" />
        <span>{{ heading }}</span>
      </h3>

      <p class="text-k-text-secondary line-clamp-1" :title="subheading">{{ subheading }}</p>

      <slot name="audio-player" />
    </div>

    <span class="absolute right-3 top-3 size-10 p-1 bg-white/10 rounded-md">
      <img alt="Koel's logo" :src="logo">
    </span>
  </header>
</template>

<script setup lang="ts">
import { getPlayableProp } from '@/utils/helpers'
import logo from '@/../img/logo.svg'

import Thumbnail from '@/components/embed/widget/EmbedWidgetThumbnail.vue'
import PreviewBadge from '@/components/embed/widget/PreviewBadge.vue'

const props = defineProps<{ embed: WidgetReadyEmbed, options: EmbedOptions }>()
const { embed, options } = props

let heading: string
let subheading: string

const setAttributes = (_heading: string, _subheading: string) => {
  heading = _heading
  subheading = _subheading
}

switch (embed.embeddable_type) {
  case 'album':
    const album = embed.embeddable as Album
    setAttributes(album.name, `Album by ${album.artist_name}`)
    break
  case 'artist':
    setAttributes((embed.embeddable as Artist).name, 'Artist')
    break
  case 'playable':
    const playable = embed.embeddable as Playable
    setAttributes(
      getPlayableProp(playable, 'title', 'title'),
      getPlayableProp(playable, 'artist_name', 'podcast_title'),
    )
    break
  case 'playlist':
    const playlist = embed.embeddable as Playlist
    setAttributes(playlist.name, playlist.description || 'Playlist')
    break
}
</script>
