<template>
  <header class="flex h-[150px] p-6 gap-5 bg-k-fg-5 shrink-0 justify-end">
    <aside class="size-[112px] aspect-square">
      <ThumbnailStack
        v-if="embed.embeddable_type === 'favorites'"
        :thumbnails="favoritesThumbnails"
        class="rounded-md"
      />
      <Thumbnail v-else :embeddable="embed.embeddable" />
    </aside>

    <div class="flex-1 min-w-0 flex flex-col justify-end gap-3">
      <h3 :title="heading" class="text-3xl text-k-fg flex items-center gap-2 pr-9 font-medium sm:text-4xl sm:font-bold">
        <PreviewBadge v-if="options.preview" />
        <MarqueeText :text="heading" />
      </h3>

      <p :title="subheading" class="line-clamp-1">{{ subheading }}</p>

      <slot name="audio-player" />
    </div>

    <span class="absolute right-3 top-3 size-10 p-1 bg-k-fg-10 rounded-md">
      <img :src="logo" alt="Koel's logo" />
    </span>
  </header>
</template>

<script setup lang="ts">
import { getPlayableProp } from '@/utils/helpers'
import { pluralize } from '@/utils/formatters'
import { useBranding } from '@/composables/useBranding'

import Thumbnail from '@/components/embed/widget/EmbedWidgetThumbnail.vue'
import PreviewBadge from '@/components/embed/widget/PreviewBadge.vue'
import ThumbnailStack from '@/components/ui/ThumbnailStack.vue'
import MarqueeText from '@/components/ui/MarqueeText.vue'

const props = defineProps<{ embed: WidgetReadyEmbed; options: EmbedOptions }>()
const { embed, options } = props

const { logo } = useBranding()

const favoritesThumbnails = Array.from(
  new Set(embed.playables.map(playable => getPlayableProp(playable, 'album_cover', 'episode_image')).filter(Boolean)),
).slice(0, 4)

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
  case 'favorites':
    setAttributes(`${(embed.embeddable as UserFavorites).owner_name}'s Favorites`, pluralize(embed.playables, 'item'))
    break
}
</script>
