<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import {
  ScrollAreaScrollbar,
  type ScrollAreaScrollbarProps,
  ScrollAreaThumb,
} from "reka-ui"
import { cn } from "@/lib/utils"

const props = withDefaults(
  defineProps<ScrollAreaScrollbarProps & { class?: HTMLAttributes["class"] }>(),
  { orientation: "vertical" },
)
</script>

<template>
  <ScrollAreaScrollbar
    v-bind="{ ...props, class: undefined }"
    :class="cn(
      'flex touch-none p-px transition-colors select-none',
      orientation === 'vertical' && 'h-full w-2.5 border-l border-l-transparent',
      orientation === 'horizontal' && 'h-2.5 flex-col border-t border-t-transparent',
      props.class,
    )"
  >
    <ScrollAreaThumb class="bg-border relative flex-1 rounded-full" />
  </ScrollAreaScrollbar>
</template>
