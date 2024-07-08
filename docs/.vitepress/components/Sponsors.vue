<template>
  <div class="sponsors">
    <h3>Proudly sponsored by</h3>
    <ul>
      <li>
        <SponsorLogo
          :logo="keycdnLogo"
          name="KeyCDN"
          slogan="Content Delivery Made Easy"
          url="https://www.keycdn.com/?a=11519"
        />
      </li>
      <li>
        <SponsorLogo
          :logo="doLogo"
          name="DigitalOcean"
          slogan="Cloud Infrastructure for Developers"
          url="https://www.digitalocean.com/?refcode=5860de5f25fe&utm_campaign=Referral_Invite&utm_medium=Referral_Program&utm_source=badge"
        />
      </li>
      <li>
        <SponsorLogo
          :logo="whatTheDiffLogo"
          name="What The Diff"
          slogan="AI powered changelog generation"
          url="https://whatthediff.ai"
        />
      </li>
      <li>
        <SponsorLogo
          :logo="rayLogo"
          name="Ray"
          slogan="Understand and fix bugs faster with Ray"
          url="https://myray.app"
        />
      </li>
      <li>
        <a class="your-logo" href="mailto:me@phanan.net?subject=Sponsoring Koel">Your logo here</a>
      </li>
    </ul>
  </div>
</template>

<script lang="ts" setup>
import SponsorLogo from './SponsorLogo.vue'

import doLogo from '../../assets/img/sponsors/do.svg'
import keycdnLogo from '../../assets/img/sponsors/keycdn.svg'
import rayLogo from '../../assets/img/sponsors/ray.svg'
import whatTheDiffLogo from '../../assets/img/sponsors/what-the-diff.svg'
</script>

<style lang="postcss">
div.sponsors {
  border-left: 1px solid var(--vp-c-divider);
  padding-left: 16px;
  font-size: 13px;

  h3 {
    font-size: 14px;
    font-weight: bold;
    margin-bottom: .75rem;
  }

  ul {
    width: 75%;
  }

  ul li + li {
    margin-top: .5rem;
  }

  .your-logo {
    margin-top: 1rem;
    display: block;
    text-align: center;
    border-radius: 5px;
    border: 1px solid var(--vp-c-border);
    line-height: 2.75;
    transition: .2s all;

    &:hover {
      border-color: var(--vp-c-brand-1);
    }
  }
}
</style>
