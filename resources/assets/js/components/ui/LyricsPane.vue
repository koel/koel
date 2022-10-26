<template>
  <article id="lyrics">
    <div class="content">
      <template v-if="song">
        <div v-show="song.lyrics">
          <pre ref="lyricsContainer">{{ song.lyrics }}</pre>
          <Magnifier @in="zoomLevel++" @out="zoomLevel--" class="magnifier"/>
        </div>
        <p v-if="song.id && !song.lyrics" class="none text-secondary">
          <template v-if="isAdmin">
            No lyrics found.
            <button class="text-highlight" data-testid="add-lyrics-btn" type="button" @click.prevent="showEditSongForm">
              Click here
            </button>
            to add lyrics.
          </template>
          <span v-else>No lyrics available. Are you listening to Bach?</span>
        </p>
      </template>
    </div>
  </article>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, onMounted, ref, toRefs, watch } from 'vue'
import { eventBus } from '@/utils'
import { useAuthorization } from '@/composables'
import { preferenceStore as preferences } from '@/stores'

const Magnifier = defineAsyncComponent(() => import('@/components/ui/Magnifier.vue'))

const { isAdmin } = useAuthorization()
const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const lyricsContainer = ref<HTMLElement>()
const zoomLevel = ref(preferences.lyricsZoomLevel || 1)

const showEditSongForm = () => eventBus.emit('MODAL_SHOW_EDIT_SONG_FORM', song.value, 'lyrics')

const setFontSize = () => {
  if (lyricsContainer.value) {
    lyricsContainer.value.style.fontSize = `${1 + (zoomLevel.value - 1) * 0.2}rem`
  }
}

watch(zoomLevel, level => {
  setFontSize()
  preferences.lyricsZoomLevel = level
})

onMounted(() => setFontSize())
</script>

<style lang="scss" scoped>
.content {
  position: relative;

  .magnifier {
    opacity: 0;
    position: absolute;
    top: 0;
    right: 0;

    @media (hover: none) {
      opacity: 1;
    }
  }

  &:hover .magnifier {
    opacity: .5;

    &:hover {
      opacity: 1;
    }
  }
}

pre {
  white-space: pre-wrap;
  line-height: 1.7;
}
</style>
