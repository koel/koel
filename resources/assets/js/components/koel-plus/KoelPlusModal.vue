<template>
  <div class="plus text-secondary" data-testid="koel-plus" tabindex="0">
    <img class="plus-icon" alt="Koel Plus" src="@/../img/koel-plus.svg" width="96">

    <main>
      <div class="intro">
        Koel Plus adds premium features on top of the default installation.<br>
        Pay <em>once</em> and enjoy all additional features forever — including those to be built into the app
        in the future!
      </div>

      <div v-show="!showingActivateLicenseForm" class="buttons" data-testid="buttons">
        <Btn big red @click.prevent="openPurchaseOverlay">Purchase Koel Plus</Btn>
        <Btn big green @click.prevent="showActivateLicenseForm">I have a license key</Btn>
      </div>

      <div v-if="showingActivateLicenseForm" class="activate-form" data-testid="activateForm">
        <ActivateLicenseForm v-if="showingActivateLicenseForm" />
        <Btn transparent class="cancel" @click.prevent="hideActivateLicenseForm">Cancel</Btn>
      </div>

      <div class="more-info">
        Visit <a href="https://koel.dev#plus" target="_blank">koel.dev</a> for more information.
      </div>
    </main>

    <footer>
      <Btn data-testid="close-modal-btn" red rounded @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useKoelPlus } from '@/composables'

import Btn from '@/components/ui/Btn.vue'
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

<style scoped lang="scss">
.plus {
  max-width: 480px;
  display: flex;
  flex-direction: column;
  align-items: center;

  main {
    padding: .7rem 1.7rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .plus-icon {
    margin-top: calc(-48px);
    border-radius: 99rem;
    border: 6px solid #fff;
  }

  .intro {
    text-align: center;
    padding: .5rem 1.5rem;
  }

  .buttons {
    display: flex;
    justify-content: center;
    gap: 1rem
  }

  .more-info {
    font-size: .9rem;
    opacity: .7;
  }

  .activate-form {
    display: flex;
    gap: .5rem;

    form {
      flex: 1;
    }

    button.cancel {
      color: var(--color-text-secondary);
    }
  }

  footer {
    margin-top: .5rem;
    width: 100%;
    text-align: center;
    padding: 1rem;
    background: rgba(0, 0, 0, .2);
  }
}
</style>
