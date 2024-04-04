<template>
  <div class="plus text-k-text-secondary max-w-[480px] flex flex-col items-center" data-testid="koel-plus" tabindex="0">
    <img
      class="-mt-[48px] rounded-full border-[6px] border-white"
      alt="Koel Plus"
      src="@/../img/koel-plus.svg"
      width="96"
    >

    <main class="!px-8 !py-5 text-center flex flex-col gap-5">
      <div>
        Koel Plus adds premium features on top of the default installation.<br>
        Pay <em>once</em> and enjoy all additional features forever — including those to be built into the app
        in the future!
      </div>

      <div v-show="!showingActivateLicenseForm" class="space-x-3" data-testid="buttons">
        <Btn big danger @click.prevent="openPurchaseOverlay">Purchase Koel Plus</Btn>
        <Btn big success @click.prevent="showActivateLicenseForm">I have a license key</Btn>
      </div>

      <div v-if="showingActivateLicenseForm" class="flex gap-3" data-testid="activateForm">
        <ActivateLicenseForm v-if="showingActivateLicenseForm" class="flex-1" />
        <Btn class="cancel" transparent @click.prevent="hideActivateLicenseForm">Cancel</Btn>
      </div>

      <div class="text-[0.9rem] opacity-70">
        Visit <a href="https://koel.dev#plus" target="_blank">koel.dev</a> for more information.
      </div>
    </main>

    <footer class="w-full text-center bg-black/20">
      <Btn data-testid="close-modal-btn" danger rounded @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useKoelPlus } from '@/composables'

import Btn from '@/components/ui/form/Btn.vue'
import ActivateLicenseForm from '@/components/koel-plus/ActivateLicenseForm.vue'

const { checkoutUrl } = useKoelPlus()

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const showingActivateLicenseForm = ref(false)

const openPurchaseOverlay = () => {
  close()
  LemonSqueezy.Url.Open(checkoutUrl.value) // @ts-ignore
}

const showActivateLicenseForm = () => (showingActivateLicenseForm.value = true)
const hideActivateLicenseForm = () => (showingActivateLicenseForm.value = false)

onMounted(() => window.createLemonSqueezy?.())
</script>
