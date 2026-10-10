<template>
  <form class="md:w-[560px]" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Album</h1>
    </header>

    <main class="space-y-5">
      <Tabs class="mt-1 -m-6">
        <TabList>
          <TabButton
            id="editAlbumTabDetails"
            :selected="currentTab === 'details'"
            aria-controls="editAlbumDetails"
            @click="currentTab = 'details'"
          >
            Details
          </TabButton>
          <TabButton
            id="editAlbumTabDescription"
            :selected="currentTab === 'description'"
            aria-controls="editAlbumDescription"
            @click="currentTab = 'description'"
          >
            Description
          </TabButton>
        </TabList>

        <TabPanelContainer>
          <TabPanel
            v-show="currentTab === 'details'"
            id="editAlbumDetails"
            aria-labelledby="editAlbumTabDetails"
            class="space-y-5"
          >
            <FormRow>
              <template #label>Name</template>
              <TextInput
                v-model="data.name"
                v-koel-focus
                name="name"
                placeholder="Album name"
                required
                title="Album name"
              />
            </FormRow>
            <div class="grid grid-cols-2 gap-2">
              <FormRow>
                <template #label>Artist</template>
                <TextInput v-model="album.artist_name" name="artist" disabled title="Artist name cannot be changed" />
              </FormRow>
              <FormRow>
                <template #label>Release year</template>
                <TextInput v-model="data.year" type="number" name="year" title="Release year" min="1000" />
              </FormRow>
            </div>
            <ArtworkField v-model="data.cover">Pick or paste a cover (optional)</ArtworkField>
          </TabPanel>

          <TabPanel
            v-show="currentTab === 'description'"
            id="editAlbumDescription"
            aria-labelledby="editAlbumTabDescription"
            class="space-y-2"
          >
            <RichTextEditor v-model="data.description" />
            <p class="text-k-fg-50">Leave empty to use the online-fetched text when applicable.</p>
          </TabPanel>
        </TabPanelContainer>
      </Tabs>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn variant="ghost" @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script setup lang="ts">
import { isEqual, omit, pick } from 'lodash-es'
import { defineAsyncComponent, onMounted, ref, toRaw } from 'vue'

import { useMessageToaster } from '@/composables/useMessageToaster'
import { useDialogBox } from '@/composables/useDialogBox'
import type { AlbumUpdateData } from '@/stores/albumStore'
import { albumStore } from '@/stores/albumStore'
import { useForm } from '@/composables/useForm'
import { encyclopediaService } from '@/services/encyclopediaService'

import FormRow from '@/components/ui/form/FormRow.vue'
import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import ArtworkField from '@/components/ui/form/ArtworkField.vue'

import TabButton from '@/components/ui/tabs/TabButton.vue'
import TabList from '@/components/ui/tabs/TabList.vue'
import TabPanel from '@/components/ui/tabs/TabPanel.vue'
import TabPanelContainer from '@/components/ui/tabs/TabPanelContainer.vue'
import Tabs from '@/components/ui/tabs/Tabs.vue'

const RichTextEditor = defineAsyncComponent(() => import('@/components/ui/form/RichTextEditor.vue'))

const props = defineProps<{ album: Album }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { album } = props

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const currentTab = ref<'details' | 'description'>('details')

const onlineDescription = ref('')

const { data, isPristine, handleSubmit } = useForm<AlbumUpdateData>({
  initialValues: { ...pick(album, 'name', 'year', 'cover'), description: album.description ?? '' },
  isPristine: (original, current) =>
    isEqual(omit(original, 'description'), omit(current, 'description')) &&
    (current.description === original.description ||
      (!original.description && current.description === onlineDescription.value)),
  onSubmit: async data => {
    const formData = structuredClone(toRaw(data))

    if (formData.description === onlineDescription.value) {
      formData.description = ''
    }

    if (formData.cover === album.cover) {
      // If the image is the same, don't send it (the image URL) to the server.
      delete formData.cover
    }

    await albumStore.update(album, formData)
  },
  onSuccess: () => {
    toastSuccess('Album updated.')
    close()
  },
})

onMounted(async () => {
  if (data.description) {
    return
  }

  onlineDescription.value = (await encyclopediaService.fetchForAlbum(album))?.wiki?.full ?? ''
  data.description ||= onlineDescription.value
})

const maybeClose = async () => {
  if (isPristine() || (await showConfirmDialog('Discard all changes?'))) {
    close()
  }
}
</script>
