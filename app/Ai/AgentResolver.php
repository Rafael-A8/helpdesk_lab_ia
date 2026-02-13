<?php
namespace App\Ai;

use App\Ai\Agents\SalesAgent;
use App\Ai\Agents\SupportAgent;
use App\Ai\Agents\FinanceAgent;

class AgentResolver
{
    public static function resolve(string $key): string
    {
        return match ($key) {
            'sales'     => SalesAgent::class,
            'support'   => SupportAgent::class,
            'finance'   => FinanceAgent::class,
            default => throw new \InvalidArgumentException('Agente inválido'),
        };
    }
}
