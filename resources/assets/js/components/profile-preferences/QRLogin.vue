<template>
  <article>
    Instead of using a password, you can scan the QR code below to log in to
    <a href="https://koel.dev/#mobile" target="_blank">Koel Player</a>
    on your mobile device.<br />
    The QR code refreshes every minute.

    <div v-if="oneTimeToken" class="relative mt-4 block w-fit overflow-hidden rounded-md">
      <img
        :src="qrCodeUrl"
        alt="QR Code"
        class="rounded-4 transition"
        :class="{ 'blur-md': paused }"
        height="192"
        width="192"
      />
      <button
        v-if="paused"
        type="button"
        class="absolute inset-0 flex items-center justify-center text-center text-sm text-white bg-black/50 cursor-pointer"
        @click.prevent="resetOneTimeToken"
      >
        Click for a new code
      </button>
    </div>
  </article>
</template>

<script lang="ts" setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useQRCode } from '@vueuse/integrations/useQRCode'
import { authService } from '@/services/authService'
import { base64Encode } from '@/utils/crypto'

const MAX_CYCLES = 5
const REFRESH_INTERVAL_MS = 60 * 1000

const qrCodeData = ref('')
const oneTimeToken = ref('')
const paused = ref(false)

watch(oneTimeToken, () => {
  qrCodeData.value = base64Encode(
    JSON.stringify({
      token: oneTimeToken.value,
      host: window.KOEL.base_url,
    }),
  )
})

const qrCodeUrl = useQRCode(qrCodeData, {
  width: window.devicePixelRatio === 1 ? 196 : 384,
  height: window.devicePixelRatio === 1 ? 196 : 384,
})

let refreshTimeout: number | null = null
let cycleCount = 0
let isUnmounted = false

const clearRefreshTimeout = () => {
  if (refreshTimeout !== null) {
    window.clearTimeout(refreshTimeout)
    refreshTimeout = null
  }
}

const resetOneTimeToken = async () => {
  const token = await authService.getOneTimeToken()

  if (isUnmounted) {
    return
  }

  oneTimeToken.value = token
  paused.value = false
  cycleCount = 0
  clearRefreshTimeout()
  scheduleNextRefresh()
}

const scheduleNextRefresh = () => {
  clearRefreshTimeout()
  refreshTimeout = window.setTimeout(async () => {
    cycleCount++

    if (cycleCount >= MAX_CYCLES) {
      paused.value = true

      return
    }

    const token = await authService.getOneTimeToken()

    if (isUnmounted) {
      return
    }

    oneTimeToken.value = token
    scheduleNextRefresh()
  }, REFRESH_INTERVAL_MS)
}

onMounted(() => resetOneTimeToken())
onBeforeUnmount(() => {
  isUnmounted = true
  clearRefreshTimeout()
})
</script>
