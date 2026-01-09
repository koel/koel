<template>
  <section>
    <h4 v-if="isPlus" class="text-xl mb-5 text-k-fg">{{ t('content.theme.builtIn') }}</h4>
    <ThemeList :themes="builtInThemes" data-testid="built-in-themes" />

    <template v-if="isPlus">
      <h4 class="text-xl mt-8 mb-5 text-k-fg">{{ t('content.theme.custom') }}</h4>
      <ThemeList v-if="customThemes.length" :themes="customThemes" class="mb-4" data-testid="custom-themes" />
      <Btn transparent bordered @click="requestCreateThemeForm">{{ t('content.theme.newTheme') }}</Btn>
    </template>
  </section>
</template>

<script lang="ts" setup>
import { computed, onMounted, toRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { themeStore } from '@/stores/themeStore'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { eventBus } from '@/utils/eventBus'

import Btn from '@/components/ui/form/Btn.vue'
import ThemeList from '@/components/profile-preferences/theme/ThemeList.vue'

const { t } = useI18n()

const themes = toRef(themeStore.state, 'themes')

const builtInThemes = computed(() => themes.value.filter(theme => !theme.is_custom))
const customThemes = computed(() => themes.value.filter(theme => theme.is_custom))

const { isPlus } = useKoelPlus()

const requestCreateThemeForm = () => eventBus.emit('MODAL_SHOW_CREATE_THEME_FORM')

onMounted(async () => {
  if (isPlus.value) {
    await themeStore.fetchCustomThemes()
  }
})
</script>
