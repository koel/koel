<template>
  <form class="md:w-2/3" @submit.prevent="handleSubmit">
    <SettingGroup>
      <div class="flex flex-col gap-2">
        <section class="flex flex-col gap-3">
          <div>
            <label class="text-k-fg" for="brandingName">App name</label>
            <p class="text-[.95rem] text-k-fg-50">Shown in the browser tab and around the app.</p>
          </div>
          <TextInput id="brandingName" v-model="data.name" name="name" placeholder="Koel" />
        </section>

        <BrandingImageField v-model="data.logo" :default="koelBirdLogo" name="logo">
          <template #label>App logo</template>
          <template #help>Favicon, app icon and logo.</template>
        </BrandingImageField>

        <BrandingImageField v-model="data.cover" :default="koelBirdCover" name="cover">
          <template #label>App cover</template>
          <template #help>For albums, artists and playlists without an image.</template>
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
import TextInput from '@/components/ui/form/TextInput.vue'
import Btn from '@/components/ui/form/Btn.vue'
import BrandingImageField from '@/components/screens/settings/BrandingImageField.vue'

const props = defineProps<{ currentBranding: Branding }>()

const { showConfirmDialog } = useDialogBox()
const { koelBirdCover, koelBirdLogo, isKoelBirdCover, isKoelBirdLogo } = useBranding()

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
