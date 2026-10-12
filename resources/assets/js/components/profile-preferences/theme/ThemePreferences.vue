<template>
  <div class="space-y-8">
    <SettingGroup>
      <template v-if="isPlus" #title>Built-in Themes</template>
      <ThemeList :themes="builtInThemes" data-testid="built-in-themes" />
    </SettingGroup>

    <SettingGroup v-if="isPlus">
      <template #title>Custom Themes</template>
      <ThemeList v-if="customThemes.length" :themes="customThemes" class="mb-4" data-testid="custom-themes" />
      <Btn variant="ghost" bordered @click="requestCreateThemeForm">New Theme</Btn>
    </SettingGroup>
  </div>
</template>

<script lang="ts" setup>
import { computed, onMounted, toRef } from 'vue'
import { themeStore } from '@/stores/themeStore'
import { defineAsyncComponent } from '@/utils/helpers'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { useModal } from '@/composables/useModal'

import Btn from '@/components/ui/form/Btn.vue'
import ThemeList from '@/components/profile-preferences/theme/ThemeList.vue'
import SettingGroup from '@/components/screens/settings/SettingGroup.vue'

const CreateThemeForm = defineAsyncComponent(() => import('@/components/profile-preferences/theme/CreateThemeForm.vue'))
const { openModal } = useModal()

const themes = toRef(themeStore.state, 'themes')

const builtInThemes = computed(() => themes.value.filter(theme => !theme.is_custom))
const customThemes = computed(() => themes.value.filter(theme => theme.is_custom))

const { isPlus } = useKoelPlus()

const requestCreateThemeForm = () => openModal<'CREATE_THEME_FORM'>(CreateThemeForm)

onMounted(async () => {
  if (isPlus.value) {
    await themeStore.fetchCustomThemes()
  }
})
</script>
