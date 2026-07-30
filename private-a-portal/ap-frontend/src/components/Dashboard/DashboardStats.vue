<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div
      v-for="stat in formattedStats"
      :key="stat.id || stat.label"
      :class="[
        stat.cardBg || 'bg-white',
        'px-6 py-5 rounded-xl shadow-md border border-gray-200 hover:shadow-2xl transition-all duration-300 group',
        stat.clickable ? 'cursor-pointer' : '',
      ]"
      @click="stat.clickable && handleClick(stat)"
    >
      <div class="flex items-start space-x-4">
        <!-- Icon container with improved styling -->
        <div
          :class="[
            stat.bgColor,
            'p-3 rounded-lg shadow-md group-hover:scale-110 group-hover:shadow-lg transition-all duration-300 flex-shrink-0 flex items-center justify-center',
          ]"
        >
          <component
            :is="getIconComponent(stat.icon)"
            :size="24"
            :class="stat.textColor"
          />
        </div>

        <!-- Stats content -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-500 mb-2">{{ stat.label }}</p>
          <p
            class="text-sm leading-tight"
            :class="stat.valueColor || 'text-gray-900'"
          >
            {{ stat.value }}
          </p>



          <!-- Optional change indicator -->
          <div v-if="stat.change" class="flex items-center mt-2">
            <component
              :is="stat.change > 0 ? ArrowUp : ArrowDown"
              :size="14"
              :class="stat.change > 0 ? 'text-green-500' : 'text-red-500'"
              class="mr-1"
            />
            <span
              :class="[
                stat.change > 0 ? 'text-green-600' : 'text-red-600',
                'text-xs font-semibold',
              ]"
            >
              {{ Math.abs(stat.change) }}%
            </span>
            <span class="text-xs text-gray-400 ml-1">vs last month</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { ArrowUp, ArrowDown, BarChart3 } from "lucide-vue-next";

const emit = defineEmits(["stat-click"]);

// Props
const props = defineProps({
  stats: {
    type: Array,
    required: true,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

// Icon mapping for dynamic icons
const getIconComponent = (iconName) => {
  const iconMap = {
    "fas fa-chart-bar": BarChart3,
    default: BarChart3,
  };
  return iconMap[iconName] || iconMap.default;
};

const handleClick = (stat) => {
  emit("stat-click", stat);
};

// Optional computed properties for enhanced stats
const formattedStats = computed(() => {
  return props.stats.map((stat) => ({
    ...stat,
    // Ensure we have default values
    cardBg: stat.cardBg || "bg-white",
    bgColor: stat.bgColor || "bg-gray-100",
    textColor: stat.textColor || "text-gray-600",
    icon: stat.icon || "fas fa-chart-bar",
    valueColor: stat.valueColor || "text-gray-900",
  }));
});
</script>

<style scoped>
/* Loading skeleton animation */
.loading-skeleton {
  background: linear-gradient(-90deg, #f0f0f0 0%, #e0e0e0 50%, #f0f0f0 100%);
  background-size: 400% 400%;
  animation: pulse 1.6s ease-in-out infinite;
}

@keyframes pulse {
  0% {
    background-position: 0% 0%;
  }
  100% {
    background-position: -135% 0%;
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
