<template>
  <footer class="text-gray-600 dark:text-gray-400 text-center p-6 mt-auto border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-[#1a1f2e]">
    <p>© {{ currentYear }}. DancDent. All rights reserved.</p>
  </footer>
</template>

<script setup>
const currentYear = new Date().getFullYear();
</script>
