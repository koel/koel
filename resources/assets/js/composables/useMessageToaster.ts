import type { Ref } from 'vue'
import { MessageToasterKey } from '@/config/symbols'
import { requireInjection } from '@/utils/helpers'

import type MessageToaster from '@/components/ui/message-toaster/MessageToaster.vue'

let toaster: Ref<InstanceType<typeof MessageToaster>>

export const useMessageToaster = () => {
  toaster = toaster || requireInjection(MessageToasterKey)

  return {
    toastSuccess: toaster.value.success.bind(toaster.value),
    toastInfo: toaster.value.info.bind(toaster.value),
    toastWarning: toaster.value.warning.bind(toaster.value),
    toastError: toaster.value.error.bind(toaster.value),
    toastLoading: toaster.value.loading.bind(toaster.value),
    toastPromise: toaster.value.promise.bind(toaster.value),
    updateToast: toaster.value.update.bind(toaster.value),
    dismissToast: toaster.value.dismiss.bind(toaster.value),
  }
}
