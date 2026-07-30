<template>
  <nav class="flex items-center text-sm bg-white/80 border border-slate-200 rounded-lg px-3 py-2 shadow-sm">
    <template v-for="(item, index) in breadcrumbs" :key="index">
      <!-- Breadcrumb Item -->
      <button
        v-if="index < breadcrumbs.length - 1"
        @click="navigateTo(item.path)"
        class="text-slate-600 hover:text-blue-600 transition-colors duration-150 font-medium inline-flex items-center"
      >
        <span class="truncate max-w-[180px]">{{ item.name }}</span>
      </button>

      <span v-else class="text-slate-900 font-semibold truncate max-w-[220px] inline-flex items-center">
        {{ item.name }}
      </span>

      <!-- Separator -->
      <span v-if="index < breadcrumbs.length - 1" class="mx-2 text-slate-300">›</span>
    </template>
  </nav>
  
</template>

<script>
export default {
  name: 'Breadcrumb',
  props: {
    breadcrumbs: {
      type: Array,
      required: true,
      default: () => []
    }
  },
  methods: {
    navigateTo(path) {
      this.$router.push(path)
    }
  }
}
</script> 