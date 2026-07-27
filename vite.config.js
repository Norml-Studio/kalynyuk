import { defineConfig } from 'vite';

/**
 * Vite config — Anna Kalynyuk child theme.
 *
 * DELIBERATE DEVIATION from the Norml Sage/Vite standard: no manifest, no
 * content-hashed filenames. Reason — this project has no CI and production has
 * no build step (see .claude/ci-cd.md: pattern local-bitbucket-prod, declared as
 * a target, deploys currently blocked). `dist/` is therefore COMMITTED to the
 * repo and rsynced as-is, and PHP cache-busts with filemtime() instead of
 * parsing a manifest. Revisit once CI exists.
 *
 * Output is intentionally flat and predictable:
 *   dist/css/main.css
 *   dist/js/main.js
 */
export default defineConfig({
  // Assets are referenced from PHP, not from an index.html — so no base rewriting.
  base: './',

  build: {
    outDir: 'dist',
    emptyOutDir: true,
    manifest: false,
    // Sourcemaps are gitignored (*.map) — dev convenience only, never shipped.
    sourcemap: true,
    // The theme targets evergreen browsers; WP admin is not in scope here.
    target: 'es2018',
    cssCodeSplit: false,

    rollupOptions: {
      input: 'src/js/main.js',
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name].js',
        // With cssCodeSplit:false Vite names the single CSS bundle `style.css`.
        // Rename it — a `style.css` inside a WordPress theme is ambiguous with
        // the theme-header file at the theme root.
        assetFileNames: ({ name }) => {
          if (name === 'style.css') return 'css/main.css';
          if (name && name.endsWith('.css')) return 'css/[name][extname]';
          return 'assets/[name][extname]';
        },
      },
    },
  },

  css: {
    devSourcemap: true,
    preprocessorOptions: {
      scss: {
        // Dart Sass module system — @use/@forward, never @import (dev-bem-scss).
        api: 'modern-compiler',
      },
    },
  },
});
