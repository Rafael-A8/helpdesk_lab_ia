<script setup>
import { router } from '@inertiajs/vue3'

function selectAgent(agent) {
    router.post('/helpdesk/threads', { agent_key: agent })
}
</script>

<template>
    <div class="p-10 space-y-4">
        <h1 class="text-2xl font-bold">Escolha o setor</h1>

        <div class="flex gap-4">
            <button @click="selectAgent('sales')" class="btn">
                Vendas
            </button>

            <button @click="selectAgent('support')" class="btn">
                Suporte
            </button>

            <button @click="selectAgent('finance')" class="btn">
                Financeiro
            </button>
        </div>
    </div>
</template>
