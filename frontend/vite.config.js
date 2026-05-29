import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

// Offline-friendly build: no CDN dependencies, all assets bundled.
export default defineConfig({
  plugins: [vue()],
  server: { host: '0.0.0.0', port: 5173 },
  build: { outDir: 'dist', assetsInlineLimit: 0 },
});
