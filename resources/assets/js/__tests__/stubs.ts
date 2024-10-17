import type { Ref } from 'vue'
import { ref } from 'vue'
import { noop } from '@/utils/helpers'

import type MessageToaster from '@/components/ui/message-toaster/MessageToaster.vue'
import type DialogBox from '@/components/ui/DialogBox.vue'
import type Overlay from '@/components/ui/Overlay.vue'

export const MessageToasterStub = ref({
  info: noop,
  success: noop,
  warning: noop,
  error: noop,
}) as unknown as Ref<InstanceType<typeof MessageToaster>>

export const DialogBoxStub = ref({
  info: noop,
  success: noop,
  warning: noop,
  error: noop,
  confirm: noop,
}) as unknown as Ref<InstanceType<typeof DialogBox>>

export const OverlayStub = ref({
  show: noop,
  hide: noop,
}) as unknown as Ref<InstanceType<typeof Overlay>>
