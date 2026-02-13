<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { ScrollAreaRoot, type ScrollAreaRootProps, ScrollAreaViewport } from "reka-ui"
import { cn } from "@/lib/utils"
import ScrollBar from "./ScrollBar.vue"

const props = defineProps<ScrollAreaRootProps & { class?: HTMLAttributes["class"] }>()
</script>

<template>
  <ScrollAreaRoot v-bind="{ ...props, class: undefined }" :class="cn('relative overflow-hidden', props.class)">
    <ScrollAreaViewport class="h-full w-full rounded-[inherit] [&>div]:!block">
      <slot />
    </ScrollAreaViewport>
    <ScrollBar />
    <ScrollAreaCorner />
  </ScrollAreaRoot>
</template>

<script lang="ts">
import { ScrollAreaCorner } from "reka-ui"
</script>
