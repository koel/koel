<template>
  <form @submit.prevent="handleSubmit">
    <SettingGroup>
      <template #title>Branding</template>

      <div class="space-y-4">
        <FormRow>
          <template #label>App name</template>
          <TextInput v-model="data.name" class="md:w-2/3" name="name" placeholder="Koel" />
        </FormRow>
        <BrandingImageField v-model="data.logo" :default="koelBirdLogo" name="logo">
          <template #label>App logo</template>
          <template #help>To be used as the favicon, app icon, and logo throughout the app.</template>
        </BrandingImageField>
        <BrandingImageField v-model="data.cover" :default="koelBirdCover" name="cover">
          <template #label>App cover</template>
          <template #help>
            To be used as the placeholder if no album art, artist image, playlist cover etc. is available.
          </template>
        </BrandingImageField>
      </div>
      <template #footer>
        <Btn type="submit" :disabled="loading">Save</Btn>
      </template>
    </SettingGroup>
  </form>
</template>

<script setup lang="ts">
import { useForm } from '@/composables/useForm'
import { useBranding } from '@/composables/useBranding'
import { settingStore } from '@/stores/settingStore'
import { forceReloadWindow } from '@/utils/helpers'
import { useDialogBox } from '@/composables/useDialogBox'

import SettingGroup from '@/components/screens/settings/SettingGroup.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import Btn from '@/components/ui/form/Btn.vue'
import BrandingImageField from '@/components/screens/settings/BrandingImageField.vue'

const props = defineProps<{ currentBranding: Branding }>()

const { showConfirmDialog } = useDialogBox()
const {
  koelBirdCover,
  koelBirdLogo,
  isKoelBirdCover,
  isKoelBirdLogo,
} = useBranding()

const { data, loading, handleSubmit } = useForm<Branding>({
  initialValues: { ...props.currentBranding },
  onSubmit: async data => {
    const submittedData: Partial<Branding> = { ...data }

    if (data.logo && isKoelBirdLogo(data.logo)) {
      delete submittedData.logo
    }

    if (data.cover && isKoelBirdCover(data.cover)) {
      delete submittedData.cover
    }

    await settingStore.updateBranding(submittedData)

    if (await showConfirmDialog('Settings saved. Reload to apply the changes?')) {
      forceReloadWindow()
    }
  },
})
</script>
