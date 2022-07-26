import { Ref, ref } from 'vue'
import { noop } from '@/utils'
import MessageToaster from '@/components/ui/MessageToaster.vue'
import DialogBox from '@/components/ui/DialogBox.vue'

export const MessageToasterStub: InstanceType<Ref<MessageToaster>> = ref({
  info: noop,
  success: noop,
  warning: noop,
  error: noop
})

export const DialogBoxStub: InstanceType<Ref<DialogBox>> = ref({
  info: noop,
  success: noop,
  warning: noop,
  error: noop,
  confirm: noop
})
