import koelBirdLogo from '@/../img/logo.svg'
import koelBirdCover from '@/../img/covers/default.svg'

export const useBranding = () => {
  const currentBranding: Branding = {
    name: window.BRANDING.name,
    logo: window.BRANDING.logo || koelBirdLogo,
    cover: window.BRANDING.cover || koelBirdCover,
  }

  const isKoelBirdLogo = (logo: string) => logo === koelBirdLogo
  const isKoelBirdCover = (cover: string) => cover === koelBirdCover

  const hasCustomBranding = !isKoelBirdLogo(currentBranding.logo)
    || !isKoelBirdCover(currentBranding.cover)
    || currentBranding.name !== 'Koel'

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
