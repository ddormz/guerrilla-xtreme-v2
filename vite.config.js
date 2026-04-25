import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

/**
 * Fix for Vite 6 + VitePWA crash: [vite:manifest] Cannot read properties of undefined (reading 'length')
 *
 * Root cause: VitePWA emits assets (manifest.webmanifest, sw.js) into the bundle without a `names` array.
 * Vite 6's internal manifest plugin (vite:manifest) accesses `chunk.names.length` without a null check,
 * causing a crash when processing these PWA assets.
 *
 * Fix: Inject a plugin that runs BEFORE vite:manifest (enforce: 'post' + name sorting ensures it runs just before)
 * to add empty `names` arrays to any bundle asset that's missing one.
 */
function fixPwaManifestCompat() {
    let manifestPlugin;

    return {
        name: 'fix-pwa-manifest-compat',
        enforce: 'post',
        configResolved(config) {
            // Find the vite:manifest plugin and monkey-patch its generateBundle
            manifestPlugin = config.plugins.find(p => p.name === 'vite:manifest');
            if (!manifestPlugin?.generateBundle) return;

            const original = manifestPlugin.generateBundle;
            manifestPlugin.generateBundle = async function (opts, bundle, isWrite) {
                // Patch assets missing the `names` property before vite:manifest processes them
                for (const chunk of Object.values(bundle)) {
                    if (chunk.type === 'asset' && !chunk.names) {
                        chunk.names = [];
                    }
                    if (chunk.type === 'asset' && !chunk.originalFileNames) {
                        chunk.originalFileNames = [];
                    }
                }
                return original.call(this, opts, bundle, isWrite);
            };
        },
    };
}

export default defineConfig({
    plugins: [
        fixPwaManifestCompat(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            manifest: {
                name: 'Guerrilla Xtreme',
                short_name: 'GX',
                description: 'Comunidad Competitiva Beyblade X',
                theme_color: '#E10600',
                background_color: '#000000',
                display: 'standalone',
                orientation: 'portrait',
                start_url: '/',
                icons: [
                    { src: '/icons/icon-192x192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/icons/icon-512x512.png', sizes: '512x512', type: 'image/png' },
                    { src: '/icons/icon-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            workbox: {
                cleanupOutdatedCaches: true,
                clientsClaim: true,
                skipWaiting: true,
                globPatterns: ['**/*.{js,css,ico,png,svg,woff2}'],
                navigateFallback: null,
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.(googleapis|gstatic)\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'google-fonts',
                            expiration: { maxEntries: 10, maxAgeSeconds: 60 * 60 * 24 * 365 },
                        },
                    },
                ],
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
