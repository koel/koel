<template>
  <form @submit.prevent="confirmThenSave">
    <SettingGroup>
      <template #title>{{ t('settings.mediaPath') }}</template>
      <p v-if="storageDriver !== 'local'">
        {{ t('settings.noLocalStorage') }}
      </p>
      <FormRow v-else>
        <template #help>
          <span id="mediaPathHelp" v-html="t('settings.mediaPathDescription')" />
        </template>

        <TextInput
          v-model="mediaPath"
          aria-describedby="mediaPathHelp"
          class="md:w-2/3"
          name="media_path"
          :placeholder="t('settings.mediaPathPlaceholder')"
        />
      </FormRow>

      <template #footer>
        <Btn data-testid="submit" type="submit">{{ t('settings.saveAndScan') }}</Btn>
      </template>
    </SettingGroup>
  </form>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { commonStore } from '@/stores/commonStore'
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

const { t } = useI18n()

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { go, url } = useRouter()
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
  showOverlay({ message: t('settings.scanning') })

  try {
    await settingStore.updateMediaPath(mediaPath.value)
    toastSuccess(t('settings.saved'))
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
    await showConfirmDialog(t('settings.changedMediaPath'), t('settings.confirm')) && await save()
  } else {
    await save()
  }
}
</script>
