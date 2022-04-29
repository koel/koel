<template>
  <article id="lyrics">
    <div class="content">
      <template v-if="song">
        <div v-show="song.lyrics">
          <div ref="lyricsContainer" v-html="song.lyrics"></div>
          <Magnifier :target="lyricsContainer"/>
        </div>
        <p v-if="song.id && !song.lyrics" class="none text-secondary">
          <template v-if="isAdmin">
            No lyrics found.
            <button class="text-orange" data-test="add-lyrics-btn" type="button" @click.prevent="showEditSongForm">
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
import { computed, defineAsyncComponent, ref, toRef, toRefs } from 'vue'
import { eventBus } from '@/utils'
import { userStore } from '@/stores'

const Magnifier = defineAsyncComponent(() => import('@/components/ui/TextMagnifier.vue'))

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const lyricsContainer = ref<HTMLElement>()
const user = toRef(userStore.state, 'current')

const isAdmin = computed(() => user.value.is_admin)

const showEditSongForm = () => eventBus.emit('MODAL_SHOW_EDIT_SONG_FORM', song.value, 'lyrics')
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
