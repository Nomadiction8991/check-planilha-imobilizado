<?php

declare(strict_types=1);

namespace App\Sync;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Orquestra o envio e o recebimento entre o notebook e o servidor.
 *
 * Envio: aplica cada alteração uma vez (o uuid guarda o que já chegou) e registra os conflitos.
 * Recebimento: devolve as alterações de outras origens a partir de um cursor.
 */
final class SyncService
{
    public const LIMITE_POR_ENVIO = 500;

    public const LIMITE_POR_RECEBIMENTO = 500;

    public function __construct(
        private readonly ChangeApplier $aplicador,
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $alteracoes
     * @return array<string, mixed>
     */
    public function enviar(Device $aparelho, array $alteracoes): array
    {
        $resumo = [
            'aplicadas' => 0,
            'ignoradas' => 0,
            'ja_recebidas' => 0,
            'conflitos' => [],
            'erros' => [],
        ];

        $this->marcarOrigem($aparelho);

        try {
            foreach ($alteracoes as $alteracao) {
                $uuid = (string) $alteracao['uuid'];

                if ($this->jaRecebida($uuid)) {
                    $resumo['ja_recebidas']++;

                    continue;
                }

                try {
                    $resultado = DB::transaction(fn (): array => $this->aplicador->aplicar($alteracao));
                } catch (QueryException $erro) {
                    $resumo['erros'][] = ['uuid' => $uuid, 'motivo' => 'Falha ao gravar no servidor.'];

                    continue;
                }

                if ($resultado['status'] === ChangeApplier::ERRO) {
                    // Não marca como recebida: o notebook pode corrigir e enviar de novo.
                    $resumo['erros'][] = ['uuid' => $uuid, 'motivo' => $resultado['motivo'] ?? 'Erro.'];

                    continue;
                }

                $this->marcarRecebida($uuid, (int) $aparelho->id);

                if ($resultado['status'] === ChangeApplier::APLICADA) {
                    $resumo['aplicadas']++;
                } elseif ($resultado['status'] === ChangeApplier::IGNORADA) {
                    $resumo['ignoradas']++;
                } else {
                    $this->registrarConflito($aparelho, $alteracao, $resultado);
                    $resumo['conflitos'][] = [
                        'uuid' => $uuid,
                        'tabela' => (string) $alteracao['tabela'],
                        'chave' => (string) $alteracao['chave'],
                        'motivo' => $resultado['motivo'] ?? 'Conflito.',
                    ];
                }
            }
        } finally {
            $this->limparOrigem();
        }

        return $resumo;
    }

    /**
     * @return array{alteracoes: list<array<string, mixed>>, cursor: int, mais: bool}
     */
    public function receber(Device $aparelho, int $depois): array
    {
        $linhas = DB::table('sincronizacao_alteracoes')
            ->where('id', '>', $depois)
            ->orderBy('id')
            ->limit(self::LIMITE_POR_RECEBIMENTO + 1)
            ->get();

        $mais = $linhas->count() > self::LIMITE_POR_RECEBIMENTO;
        $linhas = $linhas->take(self::LIMITE_POR_RECEBIMENTO);

        // O cursor avança pelas linhas lidas, inclusive as do próprio aparelho, para não reler à toa.
        $cursor = $linhas->isEmpty() ? $depois : (int) $linhas->last()->id;

        $propria = 'aparelho:' . $aparelho->id;

        $alteracoes = $linhas
            ->filter(fn (object $linha): bool => $linha->origem !== $propria)
            ->map(fn (object $linha): array => [
                'id' => (int) $linha->id,
                'uuid' => (string) $linha->uuid,
                'tabela' => (string) $linha->tabela,
                'chave' => (string) $linha->chave,
                'operacao' => (string) $linha->operacao,
                'antes' => $this->decodificar($linha->antes),
                'depois' => $this->decodificar($linha->depois),
                'origem' => (string) $linha->origem,
                'criado_em' => (string) $linha->criado_em,
            ])
            ->values()
            ->all();

        return ['alteracoes' => $alteracoes, 'cursor' => $cursor, 'mais' => $mais];
    }

    private function jaRecebida(string $uuid): bool
    {
        return DB::table('sincronizacao_envios')->where('uuid', $uuid)->exists();
    }

    private function marcarRecebida(string $uuid, int $aparelhoId): void
    {
        DB::table('sincronizacao_envios')->insert([
            'uuid' => $uuid,
            'aparelho_id' => $aparelhoId,
            'recebido_em' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $alteracao
     * @param  array<string, mixed>  $resultado
     */
    private function registrarConflito(Device $aparelho, array $alteracao, array $resultado): void
    {
        DB::table('sincronizacao_conflitos')->insert([
            'aparelho_id' => $aparelho->id,
            'tabela' => (string) $alteracao['tabela'],
            'chave' => (string) $alteracao['chave'],
            'operacao' => (string) $alteracao['operacao'],
            'antes' => $this->codificar($alteracao['antes'] ?? null),
            'depois' => $this->codificar($alteracao['depois'] ?? null),
            'motivo' => (string) ($resultado['motivo'] ?? 'Conflito.'),
            'status' => 'pendente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Marca a conexão com a origem do aparelho. Só existe no MySQL: é ele que roda os gatilhos.
     */
    private function marcarOrigem(Device $aparelho): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("SET @sincronizacao_origem = 'aparelho:" . (int) $aparelho->id . "'");
        }
    }

    private function limparOrigem(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET @sincronizacao_origem = NULL');
        }
    }

    private function decodificar(mixed $valor): ?array
    {
        if ($valor === null) {
            return null;
        }

        $decodificado = is_string($valor) ? json_decode($valor, true) : $valor;

        return is_array($decodificado) ? $decodificado : null;
    }

    /**
     * @param  array<string, mixed>|null  $valor
     */
    private function codificar(?array $valor): ?string
    {
        return $valor === null ? null : json_encode($valor, JSON_UNESCAPED_UNICODE);
    }
}
