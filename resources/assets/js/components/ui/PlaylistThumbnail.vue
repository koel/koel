<template>
  <article
    class="cover relative w-full aspect-square block rounded-md overflow-hidden bg-no-repeat bg-cover bg-center"
    data-testid="playlist-thumbnail"
  >
    <slot />
    <div
      v-if="canEditPlaylist"
      class="absolute inset-0 w-full h-full bg-black bg-opacity-50 opacity-0 hover:opacity-100 transition-opacity duration-200
             flex items-center justify-center"
    >
      <div class="border border-1.5 border-white/20 rounded-md overflow-hidden">
        <button class="p-2 hover:bg-black/50" title="Upload cover image" @click.prevent="uploadCover">
          <Icon :icon="faUpload" class="text-white text-xl" fixed-width />
        </button>
        <button
          v-if="playlist.cover"
          class="p-2 hover:bg-black/30"
          title="Remove cover image"
          @click.prevent="removeCover"
        >
          <Icon :icon="faTrash" class="text-white text-xl" fixed-width />
        </button>
      </div>
    </div>
  </article>
</template>

<script lang="ts" setup>
import { computed, ref, toRefs } from 'vue'
import { defaultCover } from '@/utils'
import { playlistStore } from '@/stores'
import { useErrorHandler, useFileReader, useKoelPlus, usePolicies } from '@/composables'
import { faTrash, faUpload } from '@fortawesome/free-solid-svg-icons'

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const { currentUserCan } = usePolicies()
const { isPlus } = useKoelPlus()

const canEditPlaylist = computed(() => currentUserCan.editPlaylist(playlist.value!))
const backgroundImage = computed(() => `url(${playlist.value.cover || defaultCover})`)

const uploadCover = () => {
  const input = document.createElement('input')
  input.setAttribute('type', 'file')
  input.setAttribute('accept', 'image/*')

  input.addEventListener('change', async () => {
    const file = input.files?.[0]

    if (!file) {
      return
    }

    const backupImage = playlist.value.cover

    try {
      useFileReader().readAsDataUrl(file, async url => {
        playlist.value!.cover = url
        await playlistStore.uploadCover(playlist.value, url)
        toastSuccess('Playlist cover updated.')
      })
    } catch (error: unknown) {
      // restore the backup image
      playlist.value.cover = backupImage
      useErrorHandler().handleHttpError(error)
    }
  })

  input.dispatchEvent(new MouseEvent('click'))
}

const removeCover = async () => await playlistStore.removeCover(playlist.value)
</script>

<style lang="postcss" scoped>
article {
  background-image: v-bind(backgroundImage);

  .thumbnail-stack {
    @apply pointer-events-none;
  }
}
</style>
