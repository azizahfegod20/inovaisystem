// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  modules: [
    '@nuxt/eslint',
    '@nuxt/ui',
    '@vueuse/nuxt'
  ],

  devtools: {
    enabled: true
  },

  css: ['~/assets/css/main.css'],

  icon: {
    localApiEndpoint: '/_icons'
  },

  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000'
    }
  },

  nitro: {
    devProxy: {
      '/api/': {
        target: (process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000') + '/api/',
        changeOrigin: true
      },
      '/sanctum/': {
        target: (process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000') + '/sanctum/',
        changeOrigin: true
      }
    },
    routeRules: {
      '/api/**': {
        proxy: (process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000') + '/api/**'
      },
      '/sanctum/**': {
        proxy: (process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000') + '/sanctum/**'
      }
    }
  },

  compatibilityDate: '2024-07-11',

  eslint: {
    config: {
      stylistic: {
        commaDangle: 'never',
        braceStyle: '1tbs'
      }
    }
  }
})
