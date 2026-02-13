<script setup lang="ts">
import { SendHorizonal } from "lucide-vue-next"
import { ref } from "vue"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { Textarea } from "@/components/ui/textarea"

const props = defineProps<{
    disabled?: boolean
}>()

const emit = defineEmits<{
    (e: "send", message: string): void
}>()

const message = ref("")

function handleSend() {
    const text = message.value.trim()
    if (!text || props.disabled) {
        return
    }
    emit("send", text)
    message.value = ""
}

function handleKeydown(event: KeyboardEvent) {
    if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault()
        handleSend()
    }
}
</script>

<template>
    <div class="flex items-end gap-2 border-t p-4">
        <Textarea
            v-model="message"
            placeholder="Digite sua mensagem..."
            class="min-h-10 max-h-32 flex-1 resize-none"
            :disabled="disabled"
            @keydown="handleKeydown"
        />
        <Button
            size="icon"
            :disabled="disabled || !message.trim()"
            @click="handleSend"
        >
            <Spinner v-if="disabled" class="size-4" />
            <SendHorizonal v-else class="size-4" />
        </Button>
    </div>
</template>
