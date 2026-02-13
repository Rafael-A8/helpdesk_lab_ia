<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { SelectIcon, SelectTrigger, type SelectTriggerProps } from "reka-ui"
import { ChevronDown } from "lucide-vue-next"
import { cn } from "@/lib/utils"

const props = defineProps<SelectTriggerProps & { class?: HTMLAttributes["class"] }>()
</script>

<template>
  <SelectTrigger
    v-bind="props"
    :class="cn(
      'border-input data-placeholder:text-muted-foreground [&_svg:not([class*=text-])]:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive dark:bg-input/30 flex h-9 w-full items-center justify-between gap-2 rounded-md border bg-transparent px-3 py-2 text-sm whitespace-nowrap shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 *:data-placeholder:line-clamp-1 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg]:size-4',
      props.class,
    )"
  >
    <slot />
    <SelectIcon as-child>
      <ChevronDown class="size-4 opacity-50" />
    </SelectIcon>
  </SelectTrigger>
</template>
