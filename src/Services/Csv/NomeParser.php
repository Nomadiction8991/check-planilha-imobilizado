<?php

declare(strict_types=1);

namespace App\Services\Csv;

use PDO;
use Exception;

/**
 * NomeParser — Extrai tipo_bem, bem e complemento do campo "Nome" do relatório de imobilizado.
 *
 * Formato CSV: "4 - CADEIRA CADEIRA TRIBUNA ALMOFADADA PULPITO"
 *   4          = código do tipo_bem
 *   texto após "-" = BEM + COMPLEMENTO (separação inteligente via lista de bens do tipo)
 *
 * Formato personalizado: "1x - Banco 2,50m [Banheiro]"
 */
class NomeParser
{
    private PDO $conexao;

    public function __construct(PDO $conexao)
    {
        $this->conexao = $conexao;
    }

    /**
     * Parseia o campo "Nome" do relatório para extrair tipo_bem, bem e complemento.
     */
    public function parsearNome(string $nome): array
    {
        $resultado = [
            'tipo_bem_codigo'    => '',
            'descricao_apos_tipo' => $nome,
            'bem'                => $nome,
            'complemento'        => '',
            'quantidade'         => 1,
            'dependencia_inline' => '',
            'nome_original'      => $nome,
        ];

        if (empty($nome)) {
            return $resultado;
        }

        // FORMATO PERSONALIZADO: "1x - Banco 2,50m [Banheiro]"
        if (preg_match('/^(\d+)x\s*\-\s*(.+)$/ui', $nome, $m)) {
            return $this->parsearFormatoPersonalizado((int) $m[1], trim($m[2]), $resultado);
        }

        // FORMATO CSV: "4 - CADEIRA CADEIRA TRIBUNA ALMOFADADA PULPITO"
        if (preg_match('/^\s*(\d{1,3})(?:[\.,]\d+)?\s*[\-–—]\s*(.+)$/u', $nome, $m)) {
            return $this->parsearFormatoCsv($m[1], trim($m[2]), $resultado);
        }

        return $resultado;
    }

    // ─── Formatos ───

    private function parsearFormatoPersonalizado(int $quantidade, string $restoNome, array $resultado): array
    {
        $dependenciaInline = '';

        // Extrair dependência entre colchetes
        if (preg_match('/^(.+?)\s*\[([^\]]+)\]\s*$/u', $restoNome, $depMatch)) {
            $restoNome         = trim($depMatch[1]);
            $dependenciaInline = trim($depMatch[2]);
        }

        // Separar bem e complemento
        if (preg_match('/^([A-ZÀ-Ú\s]+?)\s+((?:\d|[A-ZÀ-Ú]+\s+\d).+)$/ui', $restoNome, $bemMatch)) {
            $bem         = mb_strtoupper(trim($bemMatch[1]), 'UTF-8');
            $complemento = mb_strtoupper(trim($bemMatch[2]), 'UTF-8');
        } else {
            $bem         = mb_strtoupper($restoNome, 'UTF-8');
            $complemento = '';
        }

        $resultado['quantidade']         = $quantidade;
        $resultado['bem']                = $bem;
        $resultado['complemento']        = $complemento;
        $resultado['dependencia_inline'] = mb_strtoupper($dependenciaInline, 'UTF-8');
        $resultado['descricao_apos_tipo'] = $restoNome;

        return $resultado;
    }

    private function parsearFormatoCsv(string $tipoBemCodigo, string $textoAposCodigo, array $resultado): array
    {
        $resultado['tipo_bem_codigo']    = $tipoBemCodigo;
        $resultado['descricao_apos_tipo'] = $textoAposCodigo;

        $bensDoTipo = $this->obterBensDoTipo($tipoBemCodigo);

        if (!empty($bensDoTipo)) {
            return $this->separarBemEComplemento($textoAposCodigo, $bensDoTipo, $resultado);
        }

        // Sem dados do tipo_bem → fallback: tenta separar por " - "
        if (preg_match('/^(.+?)\s+\-\s+(.+)$/u', $textoAposCodigo, $parts)) {
            $resultado['bem']         = trim($parts[1]);
            $resultado['complemento'] = trim($parts[2]);
        } else {
            $resultado['bem']         = $textoAposCodigo;
            $resultado['complemento'] = '';
        }

        return $resultado;
    }

    /**
     * Separa BEM e COMPLEMENTO do texto após o código do tipo_bem.
     *
     * PASSO 1: Remove o "eco" da descrição do tipo_bem do início do texto.
     * PASSO 2: Identifica o BEM no texto restante.
     */
    private function separarBemEComplemento(string $textoAposCodigo, array $bensDoTipo, array $resultado): array
    {
        // Ordena por comprimento decrescente para casar os mais específicos primeiro
        usort($bensDoTipo, fn($a, $b) => mb_strlen($b) - mb_strlen($a));

        $textoUpper = mb_strtoupper($textoAposCodigo, 'UTF-8');
        $textoNorm  = $this->removerAcentos($textoUpper);

        // PASSO 1: Remover eco da descrição do tipo
        $textoRestante     = $textoUpper;
        $textoRestanteNorm = $textoNorm;

        foreach ($bensDoTipo as $opcao) {
            $opcaoUpper = mb_strtoupper(trim($opcao), 'UTF-8');
            $opcaoNorm  = $this->removerAcentos($opcaoUpper);

            if (strpos($textoRestanteNorm, $opcaoNorm) === 0) {
                $textoRestante     = mb_substr($textoRestante, mb_strlen($opcaoUpper));
                $textoRestanteNorm = substr($textoRestanteNorm, strlen($opcaoNorm));
                $textoRestante     = (string) preg_replace('/^\s*[\/\-\|]+\s*/', ' ', $textoRestante);
                $textoRestante     = trim($textoRestante);
                $textoRestanteNorm = (string) preg_replace('/^\s*[\/\-\|]+\s*/', ' ', $textoRestanteNorm);
                $textoRestanteNorm = trim($textoRestanteNorm);
            }
        }

        // PASSO 2: Identificar BEM no texto restante
        if (!empty($textoRestante)) {
            foreach ($bensDoTipo as $bemOpcao) {
                $bemOpcaoUpper = mb_strtoupper(trim($bemOpcao), 'UTF-8');
                $bemOpcaoNorm  = $this->removerAcentos($bemOpcaoUpper);

                if (strpos($textoRestanteNorm, $bemOpcaoNorm) === 0) {
                    $complemento = trim(mb_substr($textoRestante, mb_strlen($bemOpcaoUpper)));
                    $complemento = (string) preg_replace('/^[\s\-\/]+/', '', $complemento);

                    $resultado['bem']         = $bemOpcaoUpper;
                    $resultado['complemento'] = mb_strtoupper($complemento, 'UTF-8');

                    return $resultado;
                }
            }

            // BEM não correspondeu exatamente → usar primeira opção do tipo
            $resultado['bem']         = mb_strtoupper(trim($bensDoTipo[0]), 'UTF-8');
            $resultado['complemento'] = mb_strtoupper($textoRestante, 'UTF-8');

            return $resultado;
        }

        // Nenhum eco removido → fallback: match direto no texto completo
        foreach ($bensDoTipo as $bemOpcao) {
            $bemOpcaoNorm = $this->removerAcentos(mb_strtoupper(trim($bemOpcao), 'UTF-8'));
            if (strpos($textoNorm, $bemOpcaoNorm) === 0) {
                $resto = trim(mb_substr($textoUpper, mb_strlen(trim($bemOpcao))));
                $resto = (string) preg_replace('/^[\s\-\/]+/', '', $resto);

                $resultado['bem']         = mb_strtoupper(trim($bemOpcao), 'UTF-8');
                $resultado['complemento'] = $resto;

                return $resultado;
            }
        }

        $resultado['bem']         = $textoUpper;
        $resultado['complemento'] = '';

        return $resultado;
    }

    // ─── Auxiliares ───

    /**
     * Busca as opções de bens de um tipo_bem pelo código.
     * A descricao do tipo_bem contém as opções separadas por "/".
     *
     * @return string[]
     */
    private function obterBensDoTipo(string $tipoBemCodigo): array
    {
        if (empty($tipoBemCodigo)) {
            return [];
        }

        try {
            $stmt = $this->conexao->prepare(
                "SELECT descricao FROM tipos_bens WHERE codigo = :codigo LIMIT 1"
            );
            $stmt->execute([':codigo' => $tipoBemCodigo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || empty($row['descricao'])) {
                return [];
            }

            return array_map('trim', explode('/', $row['descricao']));
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Remove acentos de uma string (para comparação normalizada).
     */
    private function removerAcentos(string $str): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        return $converted !== false ? $converted : $str;
    }
}
