<template>
  <article
    class="cover relative w-full aspect-square block rounded-md overflow-hidden bg-no-repeat bg-cover bg-center"
    data-testid="playlist-thumbnail"
  >
    <slot />
  </article>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { useBranding } from '@/composables/useBranding'

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const { cover: defaultCover } = useBranding()

const backgroundImage = computed(() => `url(${playlist.value.cover || defaultCover})`)
</script>

<style lang="postcss" scoped>
article {
  background-image: v-bind(backgroundImage);
}
</style>
