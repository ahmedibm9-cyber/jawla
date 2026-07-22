// Tailwind v4: @theme in app.css is the canonical token source.
// This config exists only for Filament's v3-based build pipeline.
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./app/Livewire/**/*.php",
    "./app/Filament/**/*.php",
    "./vendor/filament/**/*.blade.php",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: "#F0F9E8",
          100: "#DCF0CC",
          200: "#B8E19A",
          300: "#94D268",
          400: "#7EC54E",
          500: "#6DB83B",
          600: "#5BA82E",
          700: "#4A9624",
          800: "#3A7E1C",
          900: "#2A5E14",
          950: "#1A3E0C",
        },
        accent: {
          50: "#FFFBEB",
          100: "#FEF3C7",
          200: "#FDE68A",
          300: "#FCD34D",
          400: "#FBBF24",
          500: "#F59E0B",
          600: "#D97706",
          700: "#B45309",
          800: "#92400E",
          900: "#78350F",
        },
        neutral: {
          50: "#F8FAFC",
          100: "#F1F5F9",
          200: "#E2E8F0",
          300: "#CBD5E1",
          400: "#94A3B8",
          500: "#64748B",
          600: "#475569",
          700: "#334155",
          800: "#1E293B",
          900: "#0F172A",
          950: "#020617",
        },
        success: "#6DB83B",
        warning: "#D97706",
        danger: "#DC2626",
        info: "#2563EB",
      },
      fontFamily: {
        sans: ['"IBM Plex Sans Arabic"', "system-ui", "sans-serif"],
        mono: ['"IBM Plex Mono"', "Courier New", "monospace"],
      },
    },
  },
  plugins: [],
};
