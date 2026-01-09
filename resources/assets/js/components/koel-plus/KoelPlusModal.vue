<template>
  <div class="plus max-w-[480px] flex flex-col items-center" data-testid="koel-plus" tabindex="0">
    <img
      alt="Koel Plus"
      class="-mt-[48px] rounded-full border-[6px] border-k-fg"
      src="@/../img/koel-plus.svg"
      width="96"
    >

    <main class="!px-8 !py-5 text-center flex flex-col gap-5">
      <div v-html="t('koelPlus.description')" />

      <div v-show="!showingActivateLicenseForm" class="space-x-3" data-testid="buttons">
        <Btn big danger @click.prevent="openPurchaseOverlay">{{ t('koelPlus.purchase') }}</Btn>
        <Btn big success @click.prevent="showActivateLicenseForm">{{ t('koelPlus.haveLicenseKey') }}</Btn>
      </div>

      <div v-if="showingActivateLicenseForm" class="flex gap-3" data-testid="activateForm">
        <ActivateLicenseForm v-if="showingActivateLicenseForm" class="flex-1" />
        <Btn class="cancel" transparent @click.prevent="hideActivateLicenseForm">{{ t('auth.cancel') }}</Btn>
      </div>

      <div class="text-[0.9rem] text-k-fg-70" v-html="t('koelPlus.visitForMoreInfo')" />
    </main>

    <footer class="w-full text-center bg-black/20">
      <Btn danger data-testid="close-modal-btn" rounded @click.prevent="close">{{ t('playlists.close') }}</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useKoelPlus } from '@/composables/useKoelPlus'

import Btn from '@/components/ui/form/Btn.vue'
import ActivateLicenseForm from '@/components/koel-plus/ActivateLicenseForm.vue'

const { t } = useI18n()

const emit = defineEmits<{ (e: 'close'): void }>()

const { checkoutUrl } = useKoelPlus()

const close = () => emit('close')

const showingActivateLicenseForm = ref(false)

const openPurchaseOverlay = () => {
  close()
  window.LemonSqueezy.Url.Open(checkoutUrl.value)
}

const showActivateLicenseForm = () => (showingActivateLicenseForm.value = true)
const hideActivateLicenseForm = () => (showingActivateLicenseForm.value = false)

onMounted(() => window.createLemonSqueezy?.())
</script>
