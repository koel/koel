import { requireInjection } from '@/utils'
import { DialogBoxKey } from '@/symbols'

export const useDialogBox = () => {
  const dialogBox = requireInjection(DialogBoxKey)

  return {
    showSuccessDialog: (message: string, title: string = '') => dialogBox.value.success(message, title),
    showInfoDialog: (message: string, title: string = '') => dialogBox.value.info(message, title),
    showWarningDialog: (message: string, title: string = '') => dialogBox.value.warning(message, title),
    showErrorDialog: (message: string, title: string = '') => dialogBox.value.error(message, title),
    showConfirmDialog: (message: string, title: string = '') => dialogBox.value.confirm(message, title)
  }
}
