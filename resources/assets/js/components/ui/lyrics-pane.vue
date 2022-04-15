<template>
  <article id="lyrics">
    <div class="content">
      <template v-if="song">
        <div v-show="song.lyrics">
          <div ref="lyricsContainer" v-html="song.lyrics"></div>
          <TextZoomer :target="textZoomTarget"/>
        </div>
        <p class="none text-secondary" v-if="song.id && !song.lyrics">
          <template v-if="isAdmin">
            No lyrics found.
            <button
              class="text-orange"
              @click.prevent="showEditSongForm"
              data-test="add-lyrics-btn"
            >
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
import { computed, defineAsyncComponent, onUpdated, reactive, ref, toRefs } from 'vue'
import { eventBus } from '@/utils'
import { userStore } from '@/stores'

const TextZoomer = defineAsyncComponent(() => import('@/components/ui/text-zoomer.vue'))

const props = defineProps<{ song: Song | null }>()
const { song } = toRefs(props)

const lyricsContainer = ref(null as unknown as HTMLElement)
const textZoomTarget = ref(null as unknown as HTMLElement)
const userState = reactive(userStore.state)

const showEditSongForm = () => eventBus.emit('MODAL_SHOW_EDIT_SONG_FORM', [song], 'lyrics')

const isAdmin = computed(() => userState.current.is_admin)

onUpdated(() => {
  // Since Vue's $refs are not reactive, we work around by assigning to a data property
  textZoomTarget.value = lyricsContainer.value
})
</script>

<style lang="scss" scoped>
.content {
  line-height: 1.6;
  position: relative;

  .text-zoomer {
    opacity: 0;
    position: absolute;
    top: 0;
    right: 0;

    @media (hover: none) {
      opacity: 1;
    }
  }

  &:hover .text-zoomer {
    opacity: .5;

    &:hover {
      opacity: 1;
    }
  }
}
</style>
