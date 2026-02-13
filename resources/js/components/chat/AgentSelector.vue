<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"

const agents = [
    { value: "sales", label: "Vendas" },
    { value: "support", label: "Suporte" },
    { value: "finance", label: "Financeiro" },
]

defineProps<{
    modelValue?: string
    disabled?: boolean
}>()

const emit = defineEmits<{
    (e: "update:modelValue", value: string): void
}>()
</script>

<template>
    <Select
        :model-value="modelValue"
        :disabled="disabled"
        @update:model-value="emit('update:modelValue', $event as string)"
    >
        <SelectTrigger class="w-[200px]">
            <SelectValue placeholder="Escolha um agente..." />
        </SelectTrigger>
        <SelectContent>
            <SelectItem
                v-for="agent in agents"
                :key="agent.value"
                :value="agent.value"
            >
                {{ agent.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
