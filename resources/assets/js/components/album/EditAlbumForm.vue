<template>
  <form class="md:w-[560px]" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Album</h1>
    </header>

    <main class="space-y-5">
      <AlbumOrArtistEditTabs v-model:description="data.description">
        <FormRow>
          <template #label>Name</template>
          <TextInput v-model="data.name" v-koel-focus name="name" placeholder="Album name" required />
        </FormRow>
        <div class="grid grid-cols-2 gap-2">
          <FormRow>
            <template #label>Artist</template>
            <TextInput v-model="album.artist_name" name="artist" disabled />
          </FormRow>
          <FormRow>
            <template #label>Release year</template>
            <TextInput v-model="data.year" type="number" name="year" min="1000" />
          </FormRow>
        </div>
        <ArtworkField v-model="data.cover">Pick or paste a cover (optional)</ArtworkField>
      </AlbumOrArtistEditTabs>
    </main>

    <footer>
      <Btn :disabled="loading" type="submit">Save</Btn>
      <Btn variant="neutral" @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script setup lang="ts">
import { pick } from 'lodash-es'

import type { AlbumUpdateData } from '@/stores/albumStore'
import { albumStore } from '@/stores/albumStore'
import { useAlbumOrArtistEditForm } from '@/composables/useAlbumOrArtistEditForm'
import { encyclopediaService } from '@/services/encyclopediaService'

import FormRow from '@/components/ui/form/FormRow.vue'
import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import ArtworkField from '@/components/ui/form/ArtworkField.vue'
import AlbumOrArtistEditTabs from '@/components/ui/album-artist/AlbumOrArtistEditTabs.vue'

const props = defineProps<{ album: Album }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { album } = props

const close = () => emit('close')

const { data, loading, handleSubmit, maybeClose } = useAlbumOrArtistEditForm<AlbumUpdateData>({
  initialValues: { ...pick(album, 'name', 'year', 'cover'), description: album.description ?? '' },
  artworkField: 'cover',
  fetchOnlineDescription: async () => (await encyclopediaService.fetchForAlbum(album))?.wiki?.full ?? '',
  onSubmit: async data => await albumStore.update(album, data),
  successMessage: 'Album updated.',
  onSuccess: close,
  onClose: close,
})
</script>
