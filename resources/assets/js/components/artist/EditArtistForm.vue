<template>
  <form class="md:w-[560px]" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Artist</h1>
    </header>

    <main class="space-y-5">
      <AlbumOrArtistEditTabs v-model:description="data.description">
        <FormRow>
          <template #label>Name</template>
          <TextInput v-model="data.name" v-koel-focus name="name" placeholder="Artist name" required />
        </FormRow>
        <ArtworkField v-model="data.image">Pick or paste an image (optional)</ArtworkField>
      </AlbumOrArtistEditTabs>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn variant="ghost" @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script setup lang="ts">
import { pick } from 'lodash-es'

import type { ArtistUpdateData } from '@/stores/artistStore'
import { artistStore } from '@/stores/artistStore'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useAlbumOrArtistEditForm } from '@/composables/useAlbumOrArtistEditForm'
import { encyclopediaService } from '@/services/encyclopediaService'

import FormRow from '@/components/ui/form/FormRow.vue'
import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import ArtworkField from '@/components/ui/form/ArtworkField.vue'
import AlbumOrArtistEditTabs from '@/components/ui/album-artist/AlbumOrArtistEditTabs.vue'

const props = defineProps<{ artist: Artist }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { artist } = props

const { toastSuccess } = useMessageToaster()

const close = () => emit('close')

const { data, handleSubmit, maybeClose } = useAlbumOrArtistEditForm<ArtistUpdateData>({
  initialValues: { ...pick(artist, 'name', 'image'), description: artist.description ?? '' },
  artworkField: 'image',
  fetchOnlineDescription: async () => (await encyclopediaService.fetchForArtist(artist))?.bio?.full ?? '',
  onSubmit: async data => await artistStore.update(artist, data),
  onSuccess: () => {
    toastSuccess('Artist updated.')
    close()
  },
  onClose: close,
})
</script>
