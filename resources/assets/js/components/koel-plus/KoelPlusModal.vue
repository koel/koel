<template>
  <div class="plus max-w-[480px] flex flex-col items-center" data-testid="koel-plus" tabindex="0" @keydown.esc="close">
    <img
      alt="Koel Plus"
      class="mt-[-48px] rounded-full border-[6px] border-k-fg"
      src="@/../img/koel-plus.svg"
      width="96"
    />

    <main class="px-8! py-5! text-center flex flex-col gap-5">
      <div>
        Koel Plus adds premium features on top of the default installation.<br />
        Pay <em>once</em> and enjoy all additional features forever — including those to be built into the app in the
        future!
      </div>

      <div v-show="!showingActivateLicenseForm" class="space-x-3" data-testid="buttons">
        <Btn size="large" variant="destructive" :disabled="openingCheckout" @click.prevent="openPurchaseOverlay">
          Purchase Koel Plus
        </Btn>
        <Btn size="large" variant="success" @click.prevent="showActivateLicenseForm">I have a license key</Btn>
      </div>

      <div v-if="showingActivateLicenseForm" class="flex gap-3" data-testid="activateForm">
        <ActivateLicenseForm v-if="showingActivateLicenseForm" class="flex-1" />
        <Btn variant="neutral" class="cancel" @click.prevent="hideActivateLicenseForm">Cancel</Btn>
      </div>

      <div class="text-[0.9rem] text-k-fg-70">
        Visit <a href="https://koel.dev#plus" target="_blank">koel.dev</a> for more information.
      </div>
    </main>

    <footer class="w-full text-center bg-black/20">
      <Btn variant="neutral" data-testid="close-modal-btn" rounded @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { useDialogBox } from '@/composables/useDialogBox'

import Btn from '@/components/ui/form/Btn.vue'
import ActivateLicenseForm from '@/components/koel-plus/ActivateLicenseForm.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { checkoutUrl, loadLemonSqueezy } = useKoelPlus()
const { showErrorDialog } = useDialogBox()

const close = () => emit('close')

const showingActivateLicenseForm = ref(false)
const openingCheckout = ref(false)

const openPurchaseOverlay = async () => {
  openingCheckout.value = true

  try {
    const lemonSqueezy = await loadLemonSqueezy()
    lemonSqueezy.Url.Open(checkoutUrl.value)
    close()
  } catch {
    showErrorDialog('Failed to load the checkout. Please try again.')
  } finally {
    openingCheckout.value = false
  }
}

const showActivateLicenseForm = () => (showingActivateLicenseForm.value = true)
const hideActivateLicenseForm = () => (showingActivateLicenseForm.value = false)
</script>
