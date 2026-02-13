<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from "vue"
import { Head } from "@inertiajs/vue3"
import AppLayout from "@/layouts/AppLayout.vue"
import { dashboard } from "@/routes"
import { type BreadcrumbItem } from "@/types"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Separator } from "@/components/ui/separator"
import AgentSelector from "@/components/chat/AgentSelector.vue"
import ChatMessages from "@/components/chat/ChatMessages.vue"
import ChatInput from "@/components/chat/ChatInput.vue"
import type { Message } from "@/components/chat/ChatMessages.vue"
import { index as listThreads, storeThread, showThread, streamMessage } from "@/actions/App/Http/Controllers/HelpdeskController"
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

interface ThreadListItem {
    id: number
    agent_key: string
    updated_at: string
    preview: string
}

const selectedAgent = ref<string>("")
const currentThread = ref<Thread | null>(null)
const messages = ref<Message[]>([])
const threads = ref<ThreadListItem[]>([])
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

async function fetchThreads() {
    try {
        const res = await axios.get(listThreads().url)
        threads.value = res.data.threads || []
    } catch (error) {
        console.error("Failed to fetch threads:", error)
    }
}

async function selectThread(threadItem: ThreadListItem) {
    cancelStream()
    isLoading.value = true
    messages.value = []
    selectedAgent.value = ""

    try {
        const res = await axios.get(showThread(threadItem.id).url)
        currentThread.value = res.data.thread
        messages.value = res.data.messages || []
    } finally {
        isLoading.value = false
    }
}

function formatTimeAgo(dateStr: string): string {
    const date = new Date(dateStr)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMin = Math.floor(diffMs / 60000)

    if (diffMin < 1) return "agora"
    if (diffMin < 60) return `${diffMin}min`

    const diffHours = Math.floor(diffMin / 60)
    if (diffHours < 24) return `${diffHours}h`

    const diffDays = Math.floor(diffHours / 24)
    if (diffDays < 30) return `${diffDays}d`

    return date.toLocaleDateString("pt-BR")
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
        fetchThreads()
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

onMounted(() => {
    fetchThreads()
})

onUnmounted(() => {
    cancelStream()
})
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-1 flex-col overflow-hidden lg:flex-row lg:gap-6">
                <!-- Sidebar: conversas recentes -->
                <aside class="w-full shrink-0 lg:w-56">
                    <h3 class="mb-3 text-sm font-semibold text-foreground">
                        Conversas recentes
                    </h3>
                    <nav
                        v-if="threads.length"
                        class="flex flex-col space-y-1"
                    >
                        <Button
                            v-for="thread in threads"
                            :key="thread.id"
                            variant="ghost"
                            :class="[
                                'h-auto w-full justify-start px-3 py-2 text-left',
                                { 'bg-muted': currentThread?.id === thread.id },
                            ]"
                            @click="selectThread(thread)"
                        >
                            <div class="flex w-full min-w-0 flex-col gap-0.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold">
                                        {{ agentLabels[thread.agent_key] ?? thread.agent_key }}
                                    </span>
                                    <span class="text-[10px] text-muted-foreground">
                                        {{ formatTimeAgo(thread.updated_at) }}
                                    </span>
                                </div>
                                <span class="truncate text-xs text-muted-foreground">
                                    {{ thread.preview || "Sem mensagens" }}
                                </span>
                            </div>
                        </Button>
                    </nav>
                    <p
                        v-else
                        class="text-xs text-muted-foreground"
                    >
                        Nenhuma conversa ainda.
                    </p>
                </aside>

                <Separator class="my-4 lg:hidden" />

                <!-- Chat principal -->
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
        </div>
    </AppLayout>
</template>
