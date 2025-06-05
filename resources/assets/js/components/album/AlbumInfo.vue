<template>
  <AlbumArtistInfo :mode="mode" data-testid="album-info">
    <template #header>{{ album.name }}</template>

    <template #art>
      <AlbumThumbnail :entity="album" class="group" />
    </template>

    <ParagraphSkeleton v-if="loading" />

    <template v-if="!loading && info">
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

    <template v-if="!loading && info" #footer>
      Data &copy;
      <a :href="info.url" rel="noopener" target="_blank">Last.fm</a>
    </template>
  </AlbumArtistInfo>
</template>

<script lang="ts" setup>
import { ref, toRefs, watch } from 'vue'
import { mediaInfoService } from '@/services/mediaInfoService'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { defineAsyncComponent } from '@/utils/helpers'

import AlbumThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import AlbumArtistInfo from '@/components/ui/album-artist/AlbumOrArtistInfo.vue'
import ExpandableContentBlock from '@/components/ui/album-artist/ExpandableContentBlock.vue'
import ParagraphSkeleton from '@/components/ui/skeletons/ParagraphSkeleton.vue'

const props = withDefaults(defineProps<{ album: Album, mode?: MediaInfoDisplayMode }>(), { mode: 'aside' })

const TrackList = defineAsyncComponent(() => import('@/components/album/AlbumTrackList.vue'))

const { album, mode } = toRefs(props)

const { useLastfm, useSpotify } = useThirdPartyServices()

const loading = ref(false)
const info = ref<AlbumInfo | null>(null)

watch(album, async () => {
  info.value = null

  if (useLastfm.value || useSpotify.value) {
    loading.value = true
    info.value = await mediaInfoService.fetchForAlbum(album.value)
    loading.value = false
  }
}, { immediate: true, deep: true })
</script>

<style lang="postcss" scoped>
:deep(.play-icon) {
  @apply scale-[4];
}
</style>
