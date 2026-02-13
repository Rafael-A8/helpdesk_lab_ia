<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps(['thread'])

const message = ref('')
const messages = ref([])

async function send() {
    const res = await axios.post(
        `/helpdesk/threads/${props.thread.id}/send`,
        { message: message.value }
    )

    messages.value.push({
        role: 'assistant',
        text: res.data.response
    })

    message.value = ''
}
</script>

<template>
    <div class="p-10">
        <h1 class="text-xl font-bold mb-4">
            Setor: {{ thread.agent_key }}
        </h1>

        <div class="space-y-2 mb-4">
            <div v-for="m in messages" :key="m.text">
                <div class="bg-gray-100 p-2 rounded">
                    {{ m.text }}
                </div>
            </div>
        </div>

        <input v-model="message" class="border p-2 w-full mb-2" />
        <button @click="send" class="btn">
            Enviar
        </button>
    </div>
</template>
