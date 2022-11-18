import { MessageToasterKey } from '@/symbols'
import { requireInjection } from '@/utils'

export const useMessageToaster = () => {
  const toaster = requireInjection(MessageToasterKey)

  return {
    toastSuccess: (content: string, timeout?: number) => toaster.value.success(content, timeout),
    toastInfo: (content: string, timeout?: number) => toaster.value.info(content, timeout),
    toastWarning: (content: string, timeout?: number) => toaster.value.warning(content, timeout),
    toastError: (content: string, timeout?: number) => toaster.value.error(content, timeout)
  }
}
