<template>
  <AlbumArtistInfo :mode="mode" data-testid="artist-info">
    <template #header>{{ artist.name }}</template>

    <template #art>
      <ArtistThumbnail :entity="artist" class="group" />
    </template>

    <ParagraphSkeleton v-if="loading" />

    <template v-else>
      <template v-if="info?.bio">
        <ExpandableContentBlock v-if="mode === 'aside'">
          <div v-html="info.bio.full" />
        </ExpandableContentBlock>

        <div v-else v-html="info.bio.full" />
      </template>
    </template>

    <template v-if="info && !loading" #footer>
      Data &copy;
      <a :href="info.url" rel="openener" target="_blank">Last.fm</a>
    </template>
  </AlbumArtistInfo>
</template>

<script lang="ts" setup>
import { ref, toRefs, watch } from 'vue'
import { mediaInfoService } from '@/services/mediaInfoService'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'

import ArtistThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import AlbumArtistInfo from '@/components/ui/album-artist/AlbumOrArtistInfo.vue'
import ExpandableContentBlock from '@/components/ui/album-artist/ExpandableContentBlock.vue'
import ParagraphSkeleton from '@/components/ui/skeletons/ParagraphSkeleton.vue'

const props = withDefaults(defineProps<{ artist: Artist, mode?: MediaInfoDisplayMode }>(), { mode: 'aside' })
const { artist, mode } = toRefs(props)

const { useLastfm, useSpotify } = useThirdPartyServices()

const loading = ref(false)
const info = ref<ArtistInfo | null>(null)

watch(artist, async () => {
  info.value = null

  if (useLastfm.value || useSpotify.value) {
    loading.value = true
    info.value = await mediaInfoService.fetchForArtist(artist.value)
    loading.value = false
  }
}, { immediate: true })
</script>

<style lang="postcss" scoped>
:deep(.play-icon) {
  @apply scale-[4];
}
</style>
