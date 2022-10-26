import renderLogo from '@/../img/sponsors/render.svg'
import exoscaleLogo from '@/../img/sponsors/exoscale.svg'
import keyCdnLogo from '@/../img/sponsors/keycdn.svg'
import whatTheDiffLogo from '@/../img/sponsors/what-the-diff.svg'

type Sponsor = {
  description: string
  url: string
  logo: {
    src: string
    style?: string
  }
}

export default [
  {
    description: 'Render - Cloud Hosting for Developers',
    url: 'https://render.com',
    logo: {
      src: renderLogo,
      style: 'height: 28px'
    }
  },
  {
    description: 'Exoscale - European Cloud Hosting',
    url: 'https://exoscale.ch',
    logo: {
      src: exoscaleLogo
    }
  },
  {
    description: 'KeyCDN - Content delivery made easy',
    url: 'https://www.keycdn.com?a=11519',
    logo: {
      src: keyCdnLogo
    }
  },
  {
    description: 'What The Diff - AI powered changelog generation',
    url: 'https://whatthediff.ai',
    logo: {
      src: whatTheDiffLogo,
      style: 'height: 20px'
    }
  }
] as Sponsor[]
