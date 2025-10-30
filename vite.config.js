import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import { wayfinder } from '@laravel/vite-plugin-wayfinder'

export default defineConfig({
    plugins: [
        vueDevTools({ appendTo: 'app.ts' }),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
                compilerOptions: {
                    // list your custom HTML elements here
                    isCustomElement: (tag) => ['ui-resource-renderer'].includes(tag),
                },
            },
        }),
        wayfinder({
            path: 'resources/js/wayfinder',
            formVariants: true,
        }),
    ],
})
