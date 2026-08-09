<template>
  <div class="avatar-width ring-4 ring-white mt-8 rounded-full relative overflow-hidden aspect-square">
    <UserAvatar v-if="previewedUser.avatar" :user="previewedUser" class="avatar-width" />

    <div
      class="absolute top-0 rounded-full w-full aspect-square flex items-center justify-center gap-2 pt-[50%] bg-black/50 opacity-0 hover:opacity-100 transition-opacity duration-300"
    >
      <button class="control" title="Pick a new avatar" type="button" @click.prevent="openFileDialog">
        <Icon :icon="faUpload" />
      </button>

      <button v-if="isChanged" class="control" title="Reset avatar" type="button" @click.prevent="resetAvatar">
        <Icon :icon="faRefresh" />
      </button>

      <button v-else class="control" title="Remove avatar" type="button" @click.prevent="removeAvatar">
        <Icon :icon="faTimes" />
      </button>
    </div>

    <ImageCropper v-if="cropperSource" :source="cropperSource" @cancel="onCancel" @crop="onCrop" />
  </div>
</template>

<script lang="ts" setup>
import { faRefresh, faTimes, faUpload } from '@fortawesome/free-solid-svg-icons'
import { computed, ref } from 'vue'
import { useFileDialog } from '@vueuse/core'
import { userStore } from '@/stores/userStore'
import { useFileReader } from '@/composables/useFileReader'
import { gravatar } from '@/utils/helpers'

import UserAvatar from '@/components/user/UserAvatar.vue'
import ImageCropper from '@/components/utils/ImageCropper.vue'

const props = defineProps<{ name: string; avatar?: string | null }>()
const emit = defineEmits<{ (e: 'update:avatar', avatar?: string | null): void }>()

const { open, onChange } = useFileDialog({
  accept: 'image/*',
  multiple: false,
  reset: true,
})

const openFileDialog = () => open()

const cropperSource = ref<string | null>(null)
const gravatarUrl = ref<string>()

const isChanged = computed(() => props.avatar !== undefined)

const previewedUser = computed(() => ({
  name: props.name,
  avatar: isChanged.value ? (props.avatar ?? gravatarUrl.value) : userStore.current.avatar,
}))

onChange(files => {
  if (!files?.length) {
    return
  }

  useFileReader().readAsDataUrl(files[0], url => {
    cropperSource.value = url
  })
})

const removeAvatar = async () => {
  gravatarUrl.value = await gravatar(userStore.current.email)
  emit('update:avatar', null)
}

const resetAvatar = () => {
  emit('update:avatar', undefined)
  cropperSource.value = null
}

const onCrop = (image: string) => {
  emit('update:avatar', image)
  cropperSource.value = null
}

const onCancel = () => (cropperSource.value = null)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

.control {
  @apply bg-black/5 w-[28px] aspect-square rounded-full px-2 py-1 hover:bg-black/70;
}

.avatar-width {
  @apply w-[105px];
}
</style>
