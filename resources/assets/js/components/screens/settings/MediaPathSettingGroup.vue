<template>
  <form class="md:w-2/3" @submit.prevent="confirmThenSave">
    <SettingGroup>
      <FormRow>
        <template #help>
          <span id="mediaPathHelp">
            The <em>absolute</em> path to the server directory containing your media. Koel will scan this directory for
            songs and extract any available information.<br />
            Scanning may take a while, especially if you have a lot of songs, so be patient.
          </span>
        </template>

        <TextInput
          v-model="mediaPath"
          aria-describedby="mediaPathHelp"
          name="media_path"
          placeholder="/path/to/your/music"
        />
      </FormRow>

      <template #footer>
        <Btn data-testid="submit" type="submit">Save &amp; Scan</Btn>
      </template>
    </SettingGroup>
  </form>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { settingStore } from '@/stores/settingStore'
import { useRouter } from '@/composables/useRouter'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useOverlay } from '@/composables/useOverlay'
import { useErrorHandler } from '@/composables/useErrorHandler'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SettingGroup from '@/components/screens/settings/SettingGroup.vue'

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { go, url } = useRouter()
const { showOverlay, hideOverlay } = useOverlay()

const mediaPath = ref(settingStore.state.media_path)
const originalMediaPath = mediaPath.value

const shouldWarn = computed(() => {
  // Warn the user if the media path is not empty and about to change.
  if (!originalMediaPath || !mediaPath.value) {
    return false
  }

  return originalMediaPath !== mediaPath.value.trim()
})

const save = async () => {
  showOverlay({ message: 'Scanning…' })

  try {
    await settingStore.updateMediaPath(mediaPath.value!)
    toastSuccess('Settings saved.')
    // Make sure we're back to home first.
    go(url('home'), true)
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    hideOverlay()
  }
}

const confirmThenSave = async () => {
  if (shouldWarn.value) {
    ;(await showConfirmDialog(
      'Changing the media path will essentially remove all existing local data – songs, artists, \
      albums, favorites, etc. Sure you want to proceed?',
      'Confirm',
    )) && (await save())
  } else {
    await save()
  }
}
</script>
