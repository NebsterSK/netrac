import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import type { Plugin } from 'vite';
import { defineConfig } from 'vite';
import instruckt from 'instruckt/vite'

// The instruckt plugin only applies during "serve". This stub resolves the
// virtual module during production builds so Rollup doesn't fail.
const instrucktBuildStub: Plugin = {
    name: 'instruckt-build-stub',
    apply: 'build',
    resolveId(id) {
        if (id === 'virtual:instruckt') return '\0virtual:instruckt';
    },
    load(id) {
        if (id === '\0virtual:instruckt') return '';
    },
};

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        instruckt({
            server: false,
            endpoint: '/instruckt',
            adapters: ['vue'],
            mcp: true,
        }),
        instrucktBuildStub,
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
});
