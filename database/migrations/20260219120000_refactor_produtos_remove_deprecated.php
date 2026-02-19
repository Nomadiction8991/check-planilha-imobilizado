<?php

use Phinx\Migration\AbstractMigration;

/**
 * Refatora a tabela `produtos`:
 *
 * REMOVE colunas obsoletas:
 *   - descricao_completa      (substituída por bem + complemento)
 *   - descricao_velha         (não utilizada)
 *   - editado_descricao_completa (substituída por editado_bem + editado_complemento)
 *   - doador_conjugue_id      (não utilizado)
 *   - bem_identificado        (não utilizado)
 *   - nome_planilha           (substituída por bem + complemento)
 *
 * CORRIGE colunas editado_* para serem nullable (eram NOT NULL sem default):
 *   - editado_tipo_bem_id  INT DEFAULT NULL
 *   - editado_bem          VARCHAR(255) DEFAULT NULL
 *   - editado_complemento  VARCHAR(255) DEFAULT NULL
 *
 * ADICIONA coluna:
 *   - importado TINYINT(1) NOT NULL DEFAULT 0
 *     (marca produtos que vieram de importação de planilha)
 */
class RefactorProdutosRemoveDeprecated extends AbstractMigration
{
    public function up(): void
    {
        // ── Verificar e remover colunas obsoletas (IF EXISTS evita erros em DBs parciais) ──
        $colunasDrop = [
            'descricao_completa',
            'descricao_velha',
            'editado_descricao_completa',
            'doador_conjugue_id',
            'bem_identificado',
            'nome_planilha',
        ];

        foreach ($colunasDrop as $coluna) {
            if ($this->hasColumn('produtos', $coluna)) {
                $this->execute("ALTER TABLE `produtos` DROP COLUMN `{$coluna}`");
            }
        }

        // ── Corrigir editado_tipo_bem_id: NOT NULL → nullable ──
        if ($this->hasColumn('produtos', 'editado_tipo_bem_id')) {
            $this->execute(
                "ALTER TABLE `produtos`
                 MODIFY COLUMN `editado_tipo_bem_id` int DEFAULT NULL"
            );
        }

        // ── Corrigir editado_bem: NOT NULL → nullable ──
        if ($this->hasColumn('produtos', 'editado_bem')) {
            $this->execute(
                "ALTER TABLE `produtos`
                 MODIFY COLUMN `editado_bem` varchar(255) DEFAULT NULL"
            );
        }

        // ── Corrigir editado_complemento: NOT NULL → nullable ──
        if ($this->hasColumn('produtos', 'editado_complemento')) {
            $this->execute(
                "ALTER TABLE `produtos`
                 MODIFY COLUMN `editado_complemento` varchar(255) DEFAULT NULL"
            );
        }

        // ── Adicionar coluna importado (se ainda não existir) ──
        if (!$this->hasColumn('produtos', 'importado')) {
            $this->execute(
                "ALTER TABLE `produtos`
                 ADD COLUMN `importado` tinyint(1) NOT NULL DEFAULT 0
                 AFTER `novo`"
            );
        }
    }

    public function down(): void
    {
        // Reverter: re-adicionar colunas removidas com valores default
        $this->execute(
            "ALTER TABLE `produtos`
             ADD COLUMN `descricao_completa` varchar(255) NOT NULL DEFAULT '',
             ADD COLUMN `descricao_velha` text DEFAULT NULL,
             ADD COLUMN `editado_descricao_completa` varchar(255) NOT NULL DEFAULT '',
             ADD COLUMN `doador_conjugue_id` int DEFAULT NULL,
             ADD COLUMN `bem_identificado` tinyint(1) NOT NULL DEFAULT 1,
             ADD COLUMN `nome_planilha` text DEFAULT NULL"
        );

        // Reverter editado_* para NOT NULL (com default '' para evitar erros)
        $this->execute(
            "ALTER TABLE `produtos`
             MODIFY COLUMN `editado_tipo_bem_id` int NOT NULL DEFAULT 0,
             MODIFY COLUMN `editado_bem` varchar(255) NOT NULL DEFAULT '',
             MODIFY COLUMN `editado_complemento` varchar(255) NOT NULL DEFAULT ''"
        );

        // Remover importado
        if ($this->hasColumn('produtos', 'importado')) {
            $this->execute("ALTER TABLE `produtos` DROP COLUMN `importado`");
        }
    }
}
