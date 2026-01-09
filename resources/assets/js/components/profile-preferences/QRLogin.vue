<template>
  <article>
    {{ t('content.qrLogin.insteadOfPassword') }}
    <a href="https://koel.dev/#mobile" target="_blank">{{ t('content.qrLogin.koelPlayer') }}</a>
    {{ t('content.qrLogin.onMobileDevice') }}<br>
    {{ t('content.qrLogin.refreshEvery10Minutes') }}
    <a role="button" @click.prevent="resetOneTimeToken">{{ t('content.qrLogin.refreshNow') }}</a>
    <img v-if="oneTimeToken" :src="qrCodeUrl" :alt="t('ui.altText.qrCode')" class="mt-4 rounded-4" height="192" width="192">
  </article>
</template>

<script lang="ts" setup>
import { onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQRCode } from '@vueuse/integrations/useQRCode'
import { authService } from '@/services/authService'
import { base64Encode } from '@/utils/crypto'

const { t } = useI18n()

const qrCodeData = ref('')
const oneTimeToken = ref('')

watch(oneTimeToken, () => {
  qrCodeData.value = base64Encode(JSON.stringify({
    token: oneTimeToken.value,
    host: window.BASE_URL,
  }))
})

const qrCodeUrl = useQRCode(qrCodeData, {
  width: window.devicePixelRatio === 1 ? 196 : 384,
  height: window.devicePixelRatio === 1 ? 196 : 384,
})

let oneTimeTokenTimeout: number | null = null

const resetOneTimeToken = async () => {
  oneTimeToken.value = await authService.getOneTimeToken()

  if (oneTimeTokenTimeout) {
    window.clearTimeout(oneTimeTokenTimeout)
  }

  oneTimeTokenTimeout = window.setTimeout(resetOneTimeToken, 60 * 10 * 1000)
}

onMounted(() => resetOneTimeToken())
</script>
