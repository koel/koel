import isMobile from 'ismobilejs'
import { usePolicies } from '@/composables/usePolicies'

export const canUploadFromThisDevice = () => !isMobile.any && usePolicies().currentUserCan.uploadSongs()
