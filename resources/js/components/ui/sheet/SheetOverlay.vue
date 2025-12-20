<script setup lang="ts">
import { cn } from '@/lib/utils'
import { DialogOverlay, type DialogOverlayProps } from 'reka-ui'
import { computed, ref, onMounted, onBeforeUnmount, type HTMLAttributes } from 'vue'

const props = defineProps<DialogOverlayProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const isDragging = ref(false)

const handleDragStart = (e: DragEvent) => {
  // Vérifier si c'est un drag depuis la bibliothèque (exercice)
  if (e.dataTransfer?.types.includes('application/json') || e.dataTransfer?.types.includes('text/plain')) {
    isDragging.value = true
  }
}

const handleDragEnd = () => {
  isDragging.value = false
}

onMounted(() => {
  document.addEventListener('dragstart', handleDragStart)
  document.addEventListener('dragend', handleDragEnd)
})

onBeforeUnmount(() => {
  document.removeEventListener('dragstart', handleDragStart)
  document.removeEventListener('dragend', handleDragEnd)
})
</script>

<template>
  <DialogOverlay
    data-slot="sheet-overlay"
    :class="cn(
      'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 fixed inset-0 z-50 bg-black/80',
      isDragging ? 'pointer-events-none' : '',
      props.class
    )"
    v-bind="delegatedProps"
  >
    <slot />
  </DialogOverlay>
</template>
