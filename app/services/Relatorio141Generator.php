<?php

/**
 * @deprecated Use Relatorio141Service em src/Services/Relatorio141Service.php
 * @see Relatorio141Service
 */

require_once __DIR__ . '/../../src/Services/Relatorio141Service.php';
require_once __DIR__ . '/../../src/Repositories/ComumRepository.php';
require_once __DIR__ . '/../../src/Core/ConnectionManager.php';

/**
 * Gerador de Relatórios 14.1
 * 
 * @deprecated Use Relatorio141Service
 */
class Relatorio141Generator
{
    private $pdo;
    private Relatorio141Service $service;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;

        $comumRepository = new ComumRepository($pdo);
        $this->service = new Relatorio141Service($comumRepository, $pdo);
    }

    /**
     * @deprecated Use Relatorio141Service::gerarRelatorio()
     */
    public function gerarRelatorio($id_comum)
    {
        return $this->service->gerarRelatorio($id_comum);
    }

    /**
     * @deprecated Use Relatorio141Service::renderizar()
     */
    public function renderizar($id_planilha)
    {
        return $this->service->renderizar($id_planilha);
    }

    /**
     * @deprecated Use Relatorio141Service::gerarEmBranco()
     */
    public function gerarEmBranco($num_paginas = 1)
    {
        return $this->service->gerarEmBranco($num_paginas);
    }
}
