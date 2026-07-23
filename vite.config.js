import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { bunny } from "laravel-vite-plugin/fonts";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
  plugins: [
    laravel({
      input: ["resources/css/app.css", "resources/js/app.js"],
      refresh: true,
      fonts: [
        bunny("IBM Plex Sans Arabic", {
          weights: [400, 500, 600, 700],
        }),
      ],
    }),
    tailwindcss(),
  ],
  build: {
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes("node_modules/leaflet")) {
            return "leaflet";
          }
        },
      },
    },
  },
  server: {
    watch: {
      ignored: ["**/storage/framework/views/**"],
    },
  },
});
