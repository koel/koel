<template>
  <AlbumArtistInfo :mode="mode" data-testid="album-info">
    <template #header>{{ album.name }}</template>

    <template #art>
      <AlbumThumbnail :entity="album" class="group" />
    </template>

    <ParagraphSkeleton v-if="loading" />

    <template v-if="!loading && info?.wiki">
      <div v-html="info.wiki.full" />

      <TrackList
        v-if="info.tracks?.length"
        :album="album"
        :tracks="info.tracks"
        class="mt-8"
        data-testid="album-info-tracks"
      />
    </template>

    <template v-if="!loading && info?.url" #footer>
      <a :href="info.url" rel="noopener" target="_blank">Source</a>
    </template>
  </AlbumArtistInfo>
</template>

<script lang="ts" setup>
import { ref, toRefs, watch } from 'vue'
import { encyclopediaService } from '@/services/encyclopediaService'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { defineAsyncComponent } from '@/utils/helpers'

import AlbumThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import AlbumArtistInfo from '@/components/ui/album-artist/AlbumOrArtistInfo.vue'
import ParagraphSkeleton from '@/components/ui/ParagraphSkeleton.vue'

const props = withDefaults(defineProps<{ album: Album, mode?: EncyclopediaDisplayMode }>(), { mode: 'aside' })

const TrackList = defineAsyncComponent(() => import('@/components/album/AlbumTrackList.vue'))

const { album, mode } = toRefs(props)

const { useMusicBrainz, useLastfm, useSpotify } = useThirdPartyServices()

const loading = ref(false)
const info = ref<AlbumInfo | null>(null)

watch(album, async () => {
  info.value = null

  if (useMusicBrainz.value || useLastfm.value || useSpotify.value) {
    loading.value = true
    info.value = await encyclopediaService.fetchForAlbum(album.value)
    loading.value = false
  }
}, { immediate: true, deep: true })
</script>

<style lang="postcss" scoped>
:deep(.play-icon) {
  @apply scale-[3];
}
</style>
