import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

// Offline-friendly build: no CDN dependencies, all assets bundled.
export default defineConfig({
  plugins: [vue()],
  base: '/Dental-Practical/',
  server: {
    host: '0.0.0.0',
    port: 5173,
    proxy: {
      // Proxy API calls to the Laravel backend during frontend development.
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: { outDir: 'dist', assetsInlineLimit: 0 },
});
