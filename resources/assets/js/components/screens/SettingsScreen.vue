<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader>Settings</ScreenHeader>
    </template>

    <p v-if="storageDriver !== 'local'" class="textk-text-secondary">
      Since you’re not using a cloud storage, there’s no need to set a media path.
    </p>

    <form v-else class="space-y-6" @submit.prevent="confirmThenSave">
      <FormRow>
        <template #label>Media Path</template>

        <template #help>
          <span id="mediaPathHelp">
            The <em>absolute</em> path to the server directory containing your media.
            Koel will scan this directory for songs and extract any available information.<br>
            Scanning may take a while, especially if you have a lot of songs, so be patient.
          </span>
        </template>

        <TextInput
          v-model="mediaPath"
          aria-describedby="mediaPathHelp"
          name="media_path"
          class="w-full md:!w-2/3"
        />
      </FormRow>

      <FormRow>
        <div>
          <Btn type="submit">Scan</Btn>
        </div>
      </FormRow>
    </form>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { commonStore, settingStore } from '@/stores'
import { forceReloadWindow, parseValidationError } from '@/utils'
import { useDialogBox, useMessageToaster, useOverlay, useRouter } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog, showErrorDialog } = useDialogBox()
const { go } = useRouter()
const { showOverlay, hideOverlay } = useOverlay()

const storageDriver = ref(commonStore.state.storage_driver)
const mediaPath = ref(settingStore.state.media_path)
const originalMediaPath = mediaPath.value

const shouldWarn = computed(() => {
  // Warn the user if the media path is not empty and about to change.
  if (!originalMediaPath || !mediaPath.value) {
    return false
  }

  if (storageDriver.value !== 'local') {
    return false
  }

  return originalMediaPath !== mediaPath.value.trim()
})

const save = async () => {
  showOverlay({ message: 'Scanning…' })

  try {
    await settingStore.update({ media_path: mediaPath.value })
    toastSuccess('Settings saved.')
    // Make sure we're back to home first.
    go('home')
    forceReloadWindow()
  } catch (err: any) {
    const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
    showErrorDialog(msg, 'Error')
  } finally {
    hideOverlay()
  }
}

const confirmThenSave = async () => {
  if (shouldWarn.value) {
    await showConfirmDialog('Changing the media path will essentially remove all existing local data – songs, artists, \
          albums, favorites, etc. Sure you want to proceed?', 'Confirm')
    && await save()
  } else {
    await save()
  }
}
</script>
