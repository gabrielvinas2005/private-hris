<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
    <button
      v-for="item in items"
      :key="item.label"
      @click="item.onClick"
      class="group bg-white rounded-xl shadow-sm border border-slate-200 p-8 hover:shadow-lg transition-all duration-300 transform hover:scale-105"
    >
      <div class="text-center">
        <div :class="['w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 transition-colors duration-200', item.bgClass, item.hoverBgClass]">
          <component :is="item.icon" class="w-8 h-8" :class="item.iconColor" />
        </div>
        <h4 class="text-lg font-semibold text-slate-900 mb-2">{{ item.label }}</h4>
        <p class="text-sm text-slate-600">{{ item.description }}</p>
      </div>
    </button>
  </div>
</template>

<script>
export default {
  name: 'IconGrid',
  props: {
    items: {
      type: Array,
      required: true
    }
  }
}
</script>