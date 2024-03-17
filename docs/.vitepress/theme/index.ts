import { Theme } from 'vitepress'
import DefaultTheme from 'vitepress/theme'
import UISubjectToChangeNote from '../components/UISubjectToChangeNote.vue'
import InterfaceIcon from '../components/InterfaceIcon.vue'
import Themes from '../components/Themes.vue'
import MobileAppScreenshots from '../components/MobileAppScreenshots.vue'
import PlusBadge from '../components/PlusBadge.vue'
import CaptionedImage from '../components/CaptionedImage.vue'
import Layout from '../layout/Layout.vue'
import './custom.scss'

export default {
  Layout,
  extends: DefaultTheme,
  enhanceApp({ app }) {
    app.component('InterfaceIcon', InterfaceIcon)
    app.component('Themes', Themes)
    app.component('UISubjectToChangeNote', UISubjectToChangeNote)
    app.component('MobileAppScreenshots', MobileAppScreenshots)
    app.component('PlusBadge', PlusBadge)
    app.component('CaptionedImage', CaptionedImage)
  }
} satisfies Theme
