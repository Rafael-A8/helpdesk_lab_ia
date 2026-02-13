<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
    role: string
    content: string
    class?: HTMLAttributes["class"]
}>()
</script>

<template>
    <div
        :class="cn(
            'flex w-full',
            role === 'user' ? 'justify-end' : 'justify-start',
            props.class,
        )"
    >
        <div
            :class="cn(
                'max-w-[80%] rounded-2xl px-4 py-2.5 text-sm leading-relaxed whitespace-pre-wrap',
                role === 'user'
                    ? 'bg-primary text-primary-foreground rounded-br-md'
                    : 'bg-muted text-foreground rounded-bl-md',
            )"
        >
            {{ content }}
        </div>
    </div>
</template>
