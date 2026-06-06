import koelBirdLogo from '@/../img/logo.svg'
import koelBirdCover from '@/../img/covers/default.svg'

export const useBranding = () => {
  const currentBranding: Branding = {
    name: window.KOEL.branding.name,
    logo: window.KOEL.branding.logo || koelBirdLogo,
    cover: window.KOEL.branding.cover || koelBirdCover,
  }

  const isKoelBirdLogo = (logo: string) => logo === koelBirdLogo
  const isKoelBirdCover = (cover: string) => cover === koelBirdCover

  const hasCustomBranding =
    !isKoelBirdLogo(currentBranding.logo) || !isKoelBirdCover(currentBranding.cover) || currentBranding.name !== 'Koel'

  return {
    currentBranding,
    logo: currentBranding.logo,
    cover: currentBranding.cover,
    name: currentBranding.name,
    koelBirdLogo,
    koelBirdCover,
    isKoelBirdLogo,
    isKoelBirdCover,
    hasCustomBranding,
  }
}
