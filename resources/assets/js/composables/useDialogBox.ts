import { Ref } from 'vue'
import { requireInjection } from '@/utils'
import { DialogBoxKey } from '@/symbols'
import DialogBox from '@/components/ui/DialogBox.vue'

let dialogBox: Ref<InstanceType<typeof DialogBox>>

export const useDialogBox = () => {
  dialogBox = dialogBox || requireInjection(DialogBoxKey)

  return {
    showSuccessDialog: dialogBox.value.success.bind(dialogBox.value),
    showInfoDialog: dialogBox.value.info.bind(dialogBox.value),
    showWarningDialog: dialogBox.value.warning.bind(dialogBox.value),
    showErrorDialog: dialogBox.value.error.bind(dialogBox.value),
    showConfirmDialog: dialogBox.value.confirm.bind(dialogBox.value)
  }
}
