<template>
  <el-tag v-if="normalizedName" :type="tagType" :size="size" class="ml-2">
    {{ normalizedName }}
  </el-tag>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  name: {
    type: String,
    default: "",
  },
  size: {
    type: String,
    default: "small",
  },
});

const normalizedName = computed(() => {
  const raw = (props.name || "").trim();
  if (!raw) return "";

  const name = raw.toLowerCase();
  if (name === "monthly" || name.includes("month")) return "MONTHLY";
  if (name.includes("first") || name.includes("1st")) return "1st Half";
  if (name.includes("second") || name.includes("2nd")) return "2nd Half";

  return raw;
});

const tagType = computed(() => {
  if (!props.name) return "info";
  const name = props.name.toLowerCase();
  if (name === "monthly" || name.includes("month")) {
    return "warning";
  }
  if (name.includes("first") || name.includes("1st")) {
    return "primary";
  }
  if (name.includes("second") || name.includes("2nd")) {
    return "success";
  }
  return "info";
});
</script>

<style scoped>
.ml-2 {
  margin-left: 8px;
}
</style>
