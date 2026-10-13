<template>
  <div class="grid grid-cols-2 gap-4">
    <FormRow>
      <template #label>Name *</template>
      <TextInput v-model="name" v-koel-focus name="name" placeholder="Playlist name" required />
    </FormRow>
    <FormRow>
      <template #label>Folder</template>
      <FolderSelect v-model:folder-id="folderId" v-model:folder-name="folderName" />
    </FormRow>
    <FormRow class="col-span-2">
      <template #label>Description</template>
      <TextArea v-model="description" class="h-28" name="description" placeholder="Some optional description" />
    </FormRow>
    <ArtworkField v-model="cover">Pick or paste a cover (optional)</ArtworkField>
  </div>
</template>

<script lang="ts" setup>
import FormRow from '@/components/ui/form/FormRow.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FolderSelect from '@/components/ui/form/FolderSelect.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import ArtworkField from '@/components/ui/form/ArtworkField.vue'

const name = defineModel<Playlist['name']>('name', { required: true })
const folderId = defineModel<PlaylistFolder['id'] | null | undefined>('folderId', { required: true })
const folderName = defineModel<string | null | undefined>('folderName', { required: true })
const description = defineModel<Playlist['description']>('description', { required: true })
const cover = defineModel<string | null | undefined>('cover', { required: true })
</script>
