<template>
  <div class="avatar">
    <UserAvatar v-if="profile.avatar" :user="profile" style="width: var(--w)" />

    <div class="buttons">
      <button class="upload" type="button" title="Pick a new avatar" @click.prevent="openFileDialog">
        <Icon :icon="faUpload" />
      </button>

      <button v-if="avatarChanged" type="button" class="reset" title="Reset avatar" @click.prevent="resetAvatar">
        <Icon :icon="faRefresh" />
      </button>

      <button v-else class="remove" type="button" title="Remove avatar" @click.prevent="removeAvatar">
        <Icon :icon="faTimes" />
      </button>
    </div>

    <div v-if="cropperSource" class="cropper-wrapper">
      <div>
        <Cropper
          ref="cropper"
          :src="cropperSource"
          :stencil-props="{ aspectRatio: 1 }"
          :min-height="192"
          :max-height="480"
        />
        <div class="controls">
          <Btn type="button" green @click.prevent="crop">Crop</Btn>
          <Btn type="button" red @click.prevent="cropperSource = null">Cancel</Btn>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  faRefresh,
  faTimes,
  faUpload
} from '@fortawesome/free-solid-svg-icons'
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import { computed, ref, toRefs } from 'vue'
import { useFileDialog } from '@vueuse/core'
import { userStore } from '@/stores'
import { useFileReader } from '@/composables'
import { gravatar } from '@/utils'

import UserAvatar from '@/components/user/UserAvatar.vue'
import Btn from '@/components/ui/Btn.vue'

const props = defineProps<{ profile: Pick<User, 'name' | 'avatar'> }>()
const { profile } = toRefs(props)

const cropper = ref<typeof Cropper>()

const { open: openFileDialog, onChange } = useFileDialog({
  accept: 'image/*',
  multiple: false
})

const cropperSource = ref<string | null>(null)

onChange(files => {
  if (!files?.length) {
    profile.value.avatar = userStore.current.avatar
    cropperSource.value = null
    return
  }

  useFileReader().readAsDataUrl(files[0], url => {
    cropperSource.value = url
  })
})

const removeAvatar = () => profile.value.avatar = gravatar(userStore.current.email)

const resetAvatar = () => {
  profile.value.avatar = userStore.current.avatar
  cropperSource.value = null
}

const avatarChanged = computed(() => profile.value.avatar !== userStore.current.avatar)

const crop = () => {
  const { canvas } = cropper.value!.getResult()
  profile.value.avatar = canvas.toDataURL()
  cropperSource.value = null
}
</script>


<style scoped lang="scss">
.avatar {
  --w: 105px;
  outline: rgba(255, 255, 255, .1) solid 3px;
  margin-top: 2rem;
  border-radius: 50%;
  position: relative;
  overflow: hidden;
  background: rgba(0, 0, 0, .1);
  aspect-ratio: 1 / 1;
  width: var(--w);

  .buttons {
    position: absolute;
    top: 0;
    border-radius: 50%;
    width: 100%;
    aspect-ratio: 1 / 1;
    display: flex;
    place-items: center;
    justify-content: center;
    gap: .5rem;
    padding-top: 50%;
    opacity: 0;
    transition: opacity .3s;

    button {
      background: rgba(0, 0, 0, .3);
      width: 28px;
      aspect-ratio: 1 / 1;
      border-radius: 50%;
      padding: 2px 4px;

      &:hover {
        background: rgba(0, 0, 0, .7);
      }
    }

    &:hover {
      opacity: 1;
    }
  }

  .cropper-wrapper {
    width: 100%;
    height: 100%;
    position: fixed;
    top: 0;
    left: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99;
    background: rgba(0, 0, 0, .5);

    > div {
      position: relative;
      max-width: 100%;
      max-height: 100%;
      border-radius: 5px;
      display: flex;
    }

    .controls {
      position: fixed;
      right: 1.5rem;
      top: 1.5rem;
      display: flex;
      gap: .5rem;
      flex: 1;
    }
  }
}
</style>
