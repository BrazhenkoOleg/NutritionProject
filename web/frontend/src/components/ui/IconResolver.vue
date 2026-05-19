<template>
  <component
    :is="iconComponent"
    :size="size"
    :stroke-width="strokeWidth"
  />
</template>

<script setup>
import { computed } from 'vue'
import {
  Activity,
  Apple,
  BarChart3,
  Camera,
  CheckCircle2,
  ChevronDown,
  Coffee,
  Loader2,
  LogOut,
  Moon,
  ScanSearch,
  Settings,
  Sparkles,
  Sun,
  Trash2,
  UploadCloud,
  User,
  Utensils,
  XCircle,
} from 'lucide-vue-next'

const props = defineProps({
  name: {
    type: String,
    required: true,
  },
  size: {
    type: Number,
    default: 20,
  },
  strokeWidth: {
    type: Number,
    default: 2,
  },
})

const icons = {
  Activity,
  Apple,
  BarChart3,
  Camera,
  CheckCircle2,
  ChevronDown,
  Coffee,
  Loader2,
  LogOut,
  Moon,
  ScanSearch,
  Settings,
  Sparkles,
  Sun,
  Trash2,
  UploadCloud,
  User,
  Utensils,
  XCircle,
}

const iconComponent = computed(() => icons[props.name] || Sparkles)
</script>