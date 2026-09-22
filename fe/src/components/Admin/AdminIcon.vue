<template>
  <svg
    v-if="name === 'overview'"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
  >
    <rect x="3" y="3" width="7" height="7" rx="1" />
    <rect x="14" y="3" width="7" height="7" rx="1" />
    <rect x="3" y="14" width="7" height="7" rx="1" />
    <rect x="14" y="14" width="7" height="7" rx="1" />
  </svg>

  <svg
    v-else-if="name === 'users'"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
  >
    <circle cx="9" cy="8" r="4" />
    <path d="M3 21v-2a6 6 0 0 1 12 0v2" />
    <path d="M16 4a4 4 0 0 1 0 8" />
    <path d="M17 15a6 6 0 0 1 4 6" />
  </svg>

  <svg
    v-else-if="name === 'classes'"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
  >
    <rect x="3" y="4" width="18" height="14" rx="2" />
    <path d="M8 22h8" />
    <path d="M12 18v4" />
  </svg>

  <svg
    v-else-if="name === 'roles'"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
  >
    <path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z" />
    <path d="M9 12l2 2 4-4" />
  </svg>

  <svg
    v-else-if="name === 'logout'"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
  >
    <path d="M10 17l5-5-5-5" />
    <path d="M15 12H3" />
    <path d="M15 3h5v18h-5" />
  </svg>

  <svg
    v-else-if="name === 'menu'"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
  >
    <path d="M4 6h16" />
    <path d="M4 12h16" />
    <path d="M4 18h16" />
  </svg>
</template>

<script>
export default {
  props: {
    name: {
      type: String,
      required: true,
    },
  },
}
</script>

<style scoped>
svg {
  width: 20px;
  height: 20px;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}
</style>