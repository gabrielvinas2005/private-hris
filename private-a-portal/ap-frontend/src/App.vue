<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <!-- Loading state while checking authentication -->
    <div
      v-if="isAuthenticating"
      class="min-h-screen flex items-center justify-center bg-gray-50"
    >
      <div class="text-center">
        <div
          class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"
        ></div>
        <p class="mt-4 text-gray-600">Initializing...</p>
      </div>
    </div>
    
    <!-- Main app content -->
    <router-view v-else />
  </div>
</template>

<script>
import { onMounted, ref } from "vue";
import { useAuth } from "@/composables/useAuth";
import { useRouter } from "vue-router";

export default {
  name: "App",
  setup() {
    const { initAuth, checkAuth } = useAuth();
    const router = useRouter();
    const isAuthenticating = ref(true);

    onMounted(async () => {
      // Initialize authentication state
      initAuth();
      
      // Small delay to ensure auth state is set
      await new Promise((resolve) => setTimeout(resolve, 100));
      
      isAuthenticating.value = false;
    });

    return {
      isAuthenticating,
    };
  },
};
</script>

<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
