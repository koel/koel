import { Ref, ref } from 'vue'
import { noop } from '@/utils'

import MessageToaster from '@/components/ui/MessageToaster.vue'
import DialogBox from '@/components/ui/DialogBox.vue'
import Overlay from '@/components/ui/Overlay.vue'

export const MessageToasterStub = ref({
  info: noop,
  success: noop,
  warning: noop,
  error: noop
}) as unknown as Ref<InstanceType<typeof MessageToaster>>

export const DialogBoxStub = ref({
  info: noop,
  success: noop,
  warning: noop,
  error: noop,
  confirm: noop
}) as unknown as Ref<InstanceType<typeof DialogBox>>

export const OverlayStub = ref({
  show: noop,
  hide: noop
}) as unknown as Ref<InstanceType<typeof Overlay>>
