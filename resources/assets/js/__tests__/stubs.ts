import { Ref, ref } from 'vue'
import { noop } from '@/utils'

import MessageToaster from '@/components/ui/MessageToaster.vue'
import DialogBox from '@/components/ui/DialogBox.vue'
import Overlay from '@/components/ui/Overlay.vue'

export const MessageToasterStub: Ref<InstanceType<typeof MessageToaster>> = ref({
  info: noop,
  success: noop,
  warning: noop,
  error: noop
})

export const DialogBoxStub: Ref<InstanceType<typeof DialogBox>> = ref({
  info: noop,
  success: noop,
  warning: noop,
  error: noop,
  confirm: noop
})

export const OverlayStub: Ref<InstanceType<typeof Overlay>> = ref({
  show: noop,
  hide: noop
})
