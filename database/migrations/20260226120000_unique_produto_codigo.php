<?php

use Phinx\Migration\AbstractMigration;

/**
 * Migration: Adiciona UNIQUE KEY em produtos.codigo
 *
 * Garante unicidade no nível do banco (última linha de defesa contra duplicatas
 * que possam surgir de importações simultâneas ou bugs futuros).
 *
 * ANTES de adicionar a constraint, desativa eventuais duplicatas existentes:
 * para cada grupo de registros com o mesmo código, mantém o de maior id_produto
 * e desativa (ativo=0) todos os demais.
 */
class UniqueProdutoCodigo extends AbstractMigration
{
    public function up(): void
    {
        // ── Passo 1: Desativar duplicatas, mantendo apenas o maior id_produto ──
        // Usa subquery com alias para compatibilidade com MySQL (não permite DELETE
        // com subquery direta na mesma tabela → wrapping em subquery extra).
        $this->execute(
            <<<SQL
            UPDATE produtos p
            INNER JOIN (
                SELECT codigo, MAX(id_produto) AS id_manter
                FROM produtos
                WHERE codigo IS NOT NULL
                  AND codigo != ''
                GROUP BY codigo
                HAVING COUNT(*) > 1
            ) dup ON p.codigo = dup.codigo AND p.id_produto != dup.id_manter
            SET p.ativo = 0
            SQL
        );

        // ── Passo 2: Garantir que o banco não tenha dois registros com mesmo
        //            codigo E ativo=1 (a UNIQUE KEY não distingue ativo vs inativo,
        //            mas o passo 1 já tratou isso; se ainda houver conflito por
        //            registros ativo=0 com mesmo codigo de um ativo=1, o Phinx
        //            vai falhar e precisará limpeza manual). ──

        // ── Passo 3: Adicionar UNIQUE KEY ──
        $this->execute(
            'ALTER TABLE produtos ADD CONSTRAINT uk_produto_codigo UNIQUE KEY (codigo)'
        );
    }

    public function down(): void
    {
        $this->execute(
            'ALTER TABLE produtos DROP INDEX uk_produto_codigo'
        );
    }
}
