<script setup lang="ts">
import { ref, watch, onUnmounted } from "vue"
import { Head } from "@inertiajs/vue3"
import AppLayout from "@/layouts/AppLayout.vue"
import { dashboard } from "@/routes"
import { type BreadcrumbItem } from "@/types"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import AgentSelector from "@/components/chat/AgentSelector.vue"
import ChatMessages from "@/components/chat/ChatMessages.vue"
import ChatInput from "@/components/chat/ChatInput.vue"
import type { Message } from "@/components/chat/ChatMessages.vue"
import { storeThread, showThread, streamMessage } from "@/actions/App/Http/Controllers/HelpdeskController"
import axios from "axios"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Dashboard",
        href: dashboard().url,
    },
]

interface Thread {
    id: number
    agent_key: string
    conversation_id: string
}

const selectedAgent = ref<string>("")
const currentThread = ref<Thread | null>(null)
const messages = ref<Message[]>([])
const isLoading = ref(false)
const streamingText = ref("")
const isStreaming = ref(false)
const isFetching = ref(false)

let abortController: AbortController | null = null

const agentLabels: Record<string, string> = {
    sales: "Vendas",
    support: "Suporte",
    finance: "Financeiro",
}

function cancelStream() {
    if (abortController) {
        abortController.abort()
        abortController = null
    }
    isStreaming.value = false
    isFetching.value = false
}

async function sendToStream(url: string, body: object) {
    cancelStream()

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")

    abortController = new AbortController()
    isFetching.value = true
    streamingText.value = ""

    try {
        const response = await fetch(url, {
            method: "POST",
            signal: abortController.signal,
            headers: {
                "Content-Type": "application/json",
                "Accept": "text/event-stream",
                ...(csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {}),
            },
            credentials: "same-origin",
            body: JSON.stringify(body),
        })

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`)
        }

        if (!response.body) {
            throw new Error("ReadableStream not supported")
        }

        isFetching.value = false
        isStreaming.value = true

        const reader = response.body.getReader()
        const decoder = new TextDecoder()
        let buffer = ""

        while (true) {
            const { done, value } = await reader.read()
            if (done) {
                break
            }

            buffer += decoder.decode(value, { stream: true })

            const lines = buffer.split("\n")
            buffer = lines.pop() || ""

            for (const line of lines) {
                if (!line.startsWith("data: ")) {
                    continue
                }

                const jsonStr = line.slice(6).trim()

                if (jsonStr === "[DONE]") {
                    continue
                }

                try {
                    const event = JSON.parse(jsonStr)

                    if (event.type === "text_delta" && event.delta) {
                        streamingText.value += event.delta
                    }
                } catch {
                    // skip non-JSON lines
                }
            }
        }
    } catch (error) {
        if (error instanceof DOMException && error.name === "AbortError") {
            return
        }
        console.error("Stream error:", error)
    } finally {
        if (streamingText.value) {
            messages.value.push({
                role: "assistant",
                content: streamingText.value,
            })
        }
        streamingText.value = ""
        isStreaming.value = false
        isFetching.value = false
        abortController = null
    }
}

watch(selectedAgent, async (agentKey) => {
    if (!agentKey) {
        return
    }

    isLoading.value = true
    currentThread.value = null
    messages.value = []

    try {
        cancelStream()

        const res = await axios.post(storeThread().url, {
            agent_key: agentKey,
        })

        currentThread.value = res.data.thread

        const threadRes = await axios.get(showThread(res.data.thread.id).url)
        messages.value = threadRes.data.messages || []
    } finally {
        isLoading.value = false
    }
})

function handleSend(text: string) {
    if (!currentThread.value) {
        return
    }

    messages.value.push({
        role: "user",
        content: text,
    })

    sendToStream(streamMessage(currentThread.value.id).url, { message: text })
}

onUnmounted(() => {
    cancelStream()
})
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Card class="flex flex-1 flex-col overflow-hidden">
                <CardHeader class="flex-row items-center justify-between gap-4 space-y-0 border-b">
                    <CardTitle class="text-lg">
                        <template v-if="currentThread">
                            Chat — {{ agentLabels[currentThread.agent_key] ?? currentThread.agent_key }}
                        </template>
                        <template v-else>
                            Helpdesk AI
                        </template>
                    </CardTitle>
                    <AgentSelector
                        v-model="selectedAgent"
                        :disabled="isStreaming || isFetching"
                    />
                </CardHeader>

                <CardContent class="flex flex-1 flex-col overflow-hidden p-0">
                    <template v-if="currentThread">
                        <div class="flex-1 overflow-hidden">
                            <ChatMessages
                                :messages="messages"
                                :streaming-content="isStreaming ? streamingText : undefined"
                            />
                        </div>
                        <ChatInput
                            :disabled="isStreaming || isFetching"
                            @send="handleSend"
                        />
                    </template>

                    <div v-else class="flex flex-1 items-center justify-center">
                        <div class="text-center">
                            <p class="text-muted-foreground text-lg font-medium">
                                Selecione um agente para começar
                            </p>
                            <p class="text-muted-foreground/60 mt-1 text-sm">
                                Escolha entre Vendas, Suporte ou Financeiro
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
