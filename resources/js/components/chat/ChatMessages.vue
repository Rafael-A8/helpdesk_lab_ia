<script setup lang="ts">
import { ref, watch, nextTick } from "vue"
import ChatMessage from "./ChatMessage.vue"
import { ScrollArea } from "@/components/ui/scroll-area"

export interface Message {
    role: string
    content: string
}

const props = defineProps<{
    messages: Message[]
    streamingContent?: string
}>()

const scrollContainer = ref<InstanceType<typeof ScrollArea> | null>(null)

function scrollToBottom() {
    nextTick(() => {
        const el = scrollContainer.value?.$el
        if (el) {
            const viewport = el.querySelector("[data-reka-scroll-area-viewport]")
            if (viewport) {
                viewport.scrollTop = viewport.scrollHeight
            }
        }
    })
}

watch(
    () => [props.messages.length, props.streamingContent],
    () => scrollToBottom(),
    { deep: true },
)
</script>

<template>
    <ScrollArea ref="scrollContainer" class="h-full pr-4">
        <div class="flex flex-col gap-3 p-4">
            <ChatMessage
                v-for="(msg, index) in messages"
                :key="index"
                :role="msg.role"
                :content="msg.content"
            />

            <ChatMessage
                v-if="streamingContent"
                role="assistant"
                :content="streamingContent"
            />

            <div v-if="messages.length === 0 && !streamingContent" class="flex flex-1 items-center justify-center py-20">
                <p class="text-muted-foreground text-sm">
                    Envie uma mensagem para iniciar a conversa.
                </p>
            </div>
        </div>
    </ScrollArea>
</template>
