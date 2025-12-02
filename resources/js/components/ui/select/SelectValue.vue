<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  placeholder?: string
}>()

const placeholder = computed(() => props.placeholder || 'Sélectionner...')
</script>

<template>
  <span class="text-muted-foreground">
    <slot>{{ placeholder }}</slot>
  </span>
</template>

