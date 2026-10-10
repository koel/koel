<template>
  <form class="md:w-[560px]" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Artist</h1>
    </header>

    <main class="space-y-5">
      <Tabs class="mt-1 -m-6">
        <TabList>
          <TabButton
            id="editArtistTabDetails"
            :selected="currentTab === 'details'"
            aria-controls="editArtistDetails"
            @click="currentTab = 'details'"
          >
            Details
          </TabButton>
          <TabButton
            id="editArtistTabDescription"
            :selected="currentTab === 'description'"
            aria-controls="editArtistDescription"
            @click="currentTab = 'description'"
          >
            Description
          </TabButton>
        </TabList>

        <TabPanelContainer>
          <TabPanel
            v-show="currentTab === 'details'"
            id="editArtistDetails"
            aria-labelledby="editArtistTabDetails"
            class="space-y-5"
          >
            <FormRow>
              <template #label>Name</template>
              <TextInput
                v-model="data.name"
                v-koel-focus
                name="name"
                placeholder="Artist name"
                required
                title="Artist name"
              />
            </FormRow>
            <ArtworkField v-model="data.image">Pick or paste an image (optional)</ArtworkField>
          </TabPanel>

          <TabPanel
            v-show="currentTab === 'description'"
            id="editArtistDescription"
            aria-labelledby="editArtistTabDescription"
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

import type { ArtistUpdateData } from '@/stores/artistStore'
import { artistStore } from '@/stores/artistStore'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useDialogBox } from '@/composables/useDialogBox'
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

const props = defineProps<{ artist: Artist }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { artist } = props

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const currentTab = ref<'details' | 'description'>('details')

const onlineDescription = ref('')

const { data, isPristine, handleSubmit } = useForm<ArtistUpdateData>({
  initialValues: { ...pick(artist, 'name', 'image'), description: artist.description ?? '' },
  isPristine: (original, current) =>
    isEqual(omit(original, 'description'), omit(current, 'description')) &&
    [original.description, onlineDescription.value].includes(current.description),
  onSubmit: async data => {
    const formData = structuredClone(toRaw(data))

    if (formData.description === onlineDescription.value) {
      formData.description = ''
    }

    if (formData.image === artist.image) {
      // If the image is the same, don't send it (the image URL) to the server.
      delete formData.image
    }

    await artistStore.update(artist, formData)
  },
  onSuccess: () => {
    toastSuccess('Artist updated.')
    close()
  },
})

onMounted(async () => {
  if (data.description) {
    return
  }

  onlineDescription.value = (await encyclopediaService.fetchForArtist(artist))?.bio?.full ?? ''
  data.description ||= onlineDescription.value
})

const maybeClose = async () => {
  if (isPristine() || (await showConfirmDialog('Discard all changes?'))) {
    close()
  }
}
</script>
