import { defineConfig } from "cypress";

export default defineConfig({
  projectId: "t39zbg",
  viewportWidth: 1440,
  viewportHeight: 768,

  retries: {
    runMode: 3,
  },

  video: true,

  e2e: {
    // We've imported your old cypress plugins here.
    // You may want to clean this up later by importing these.
    setupNodeEvents(on, config) {
      return require("./cypress/plugins/index.ts").default(on, config);
    },
    baseUrl: "http://localhost:8080",
    specPattern: "cypress/e2e/**/*.{js,jsx,ts,tsx}",
  },

  component: {
    devServer: {
      framework: "vue",
      bundler: "webpack",
    },
  },
});
