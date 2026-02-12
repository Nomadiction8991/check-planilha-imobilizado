<?php

use Phinx\Migration\AbstractMigration;

class AddComumIdToUsuarios extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('usuarios');
        
        $table->addColumn('comum_id', 'integer', [
            'null' => true,
            'after' => 'ativo',
            'comment' => 'ID da comum que o usuário está trabalhando atualmente'
        ])
        ->addForeignKey('comum_id', 'comums', 'id', [
            'delete' => 'SET_NULL',
            'update' => 'CASCADE'
        ])
        ->addIndex(['comum_id'])
        ->update();
    }
}
