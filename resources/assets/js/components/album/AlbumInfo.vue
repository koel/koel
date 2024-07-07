<template>
  <AlbumArtistInfo :mode="mode" data-testid="album-info">
    <template #header>{{ album.name }}</template>

    <template #art>
      <AlbumThumbnail :entity="album" />
    </template>

    <template v-if="info">
      <template v-if="info.wiki">
        <ExpandableContentBlock v-if="mode === 'aside'">
          <div v-html="info.wiki.full" />
        </ExpandableContentBlock>

        <div v-else v-html="info.wiki.full" />
      </template>

      <TrackList
        v-if="info.tracks?.length"
        :album="album"
        :tracks="info.tracks"
        class="mt-8"
        data-testid="album-info-tracks"
      />
    </template>

    <template v-if="info" #footer>
      Data &copy;
      <a :href="info.url" rel="noopener" target="_blank">Last.fm</a>
    </template>
  </AlbumArtistInfo>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, ref, toRefs, watch } from 'vue'
import { mediaInfoService } from '@/services'
import { useThirdPartyServices } from '@/composables'

import AlbumThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import AlbumArtistInfo from '@/components/ui/album-artist/AlbumOrArtistInfo.vue'
import ExpandableContentBlock from '@/components/ui/album-artist/ExpandableContentBlock.vue'

const TrackList = defineAsyncComponent(() => import('@/components/album/AlbumTrackList.vue'))

const props = withDefaults(defineProps<{ album: Album, mode?: MediaInfoDisplayMode }>(), { mode: 'aside' })
const { album, mode } = toRefs(props)

const { useLastfm, useSpotify } = useThirdPartyServices()

const info = ref<AlbumInfo | null>(null)

watch(album, async () => {
  info.value = null

  if (useLastfm.value || useSpotify.value) {
    info.value = await mediaInfoService.fetchForAlbum(album.value)
  }
}, { immediate: true })
</script>
