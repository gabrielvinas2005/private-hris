<template>
  <Teleport to="body">
    <div v-if="modelValue" class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="onBackdrop"></div>
      <div class="relative mx-auto mt-8 mb-8 w-[95%]" :class="maxWidthClass">
        <div class="bg-white rounded-xl shadow-2xl ring-1 ring-black/5 overflow-hidden">
          <div v-if="$slots.header || title" class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
            <slot name="header">
              <h4 class="text-base font-semibold text-gray-900">{{ title }}</h4>
            </slot>
            <button @click="close" class="inline-flex items-center justify-center h-9 w-9 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100" aria-label="Close">
              ✕
            </button>
          </div>
          <div class="p-4">
            <slot />
          </div>
          <div v-if="$slots.footer" class="px-6 py-4 border-t bg-gray-50">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </div>
  </Teleport>
  
</template>

<script>
export default {
  name: 'BaseModal',
  props: {
    modelValue: { type: Boolean, required: true },
    title: { type: String, default: '' },
    closeOnBackdrop: { type: Boolean, default: true },
    maxWidth: { type: String, default: '6xl' } // sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl
  },
  computed: {
    maxWidthClass() {
      const map = { sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg', xl: 'max-w-xl', '2xl': 'max-w-2xl', '3xl': 'max-w-3xl', '4xl': 'max-w-4xl', '5xl': 'max-w-5xl', '6xl': 'max-w-6xl' }
      return map[this.maxWidth] || 'max-w-6xl'
    }
  },
  methods: {
    close() {
      this.$emit('update:modelValue', false)
    },
    onBackdrop() {
      if (this.closeOnBackdrop) this.close()
    }
  }
}
</script>

<style scoped>
</style>


