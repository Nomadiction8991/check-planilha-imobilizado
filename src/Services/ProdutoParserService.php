<?php


class ProdutoParserService
{
    
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    
    public function normalizar(string $str): string
    {
        $str = trim($str);
        $str = preg_replace('/\s+/', ' ', $str);

        
        $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        if ($s === false) {
            $s = $str;
        }

        return strtoupper($s);
    }

    
    public function normalizarChar(string $char): string
    {
        $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $char);
        if ($s === false) {
            $s = $char;
        }

        return strtoupper($s);
    }

    
    public function gerarVariacoes(string $str): array
    {
        $str = trim($str);
        if ($str === '') {
            return [];
        }

        $strNorm = $this->normalizar($str);
        $variacoes = [$strNorm];

        
        $palavras = preg_split('/\s+/', $strNorm);

        if (count($palavras) === 1) {
            
            if (substr($strNorm, -1) === 'S' && strlen($strNorm) > 2) {
                
                $variacoes[] = substr($strNorm, 0, -1);
            } else {
                
                $variacoes[] = $strNorm . 'S';
            }
        } else {
            
            $primeiraPalavra = $palavras[0];
            $resto = implode(' ', array_slice($palavras, 1));

            $primeiraVariada = null;
            if (substr($primeiraPalavra, -1) === 'S' && strlen($primeiraPalavra) > 2) {
                $primeiraVariada = substr($primeiraPalavra, 0, -1);
            } else {
                $primeiraVariada = $primeiraPalavra . 'S';
            }

            if ($primeiraVariada) {
                $variacoes[] = $primeiraVariada . ' ' . $resto;
            }
        }

        return array_unique($variacoes);
    }

    
    public function matchFuzzy(string $str1, string $str2): bool
    {
        $vars1 = $this->gerarVariacoes($str1);
        $vars2 = $this->gerarVariacoes($str2);

        foreach ($vars1 as $v1) {
            foreach ($vars2 as $v2) {
                if ($v1 === $v2) {
                    return true;
                }
            }
        }

        return false;
    }

    
    public function colunaParaIndice(string $coluna): int
    {
        $coluna = strtoupper($coluna);
        $indice = 0;
        $tamanho = strlen($coluna);

        for ($i = 0; $i < $tamanho; $i++) {
            $indice = $indice * 26 + (ord($coluna[$i]) - ord('A') + 1);
        }

        return $indice - 1;
    }

    
    public function extrairCodigoPrefixo(string $texto): array
    {
        $codigoDetectado = null;

        
        if (preg_match('/^\s*(\d{1,3})(?:[\.,]\d+)?\s*\-\s*/u', $texto, $m)) {
            $codigoDetectado = (int) $m[1];
            $texto = preg_replace('/^\s*' . preg_quote($m[0], '/') . '/u', '', $texto);
        } elseif (preg_match('/^\s*OT-?\d+\s*\-\s*/iu', $texto)) {
            
            $texto = preg_replace('/^\s*OT-?\d+\s*\-\s*/iu', '', $texto);
        }

        return [$codigoDetectado, trim($texto)];
    }

    
    public function construirAliasesTipos(array $tiposBens): array
    {
        $tiposAliases = [];

        foreach ($tiposBens as $tb) {
            $desc = (string) $tb['descricao'];

            
            $aliases = array_filter(
                array_map('trim', preg_split('/\s*\/\s*/', $desc))
            );

            
            $aliasesExpandidos = [];
            foreach ($aliases as $alias) {
                $variacoes = $this->gerarVariacoes($alias);
                $aliasesExpandidos = array_merge($aliasesExpandidos, $variacoes);
            }

            $aliasesNorm = array_unique($aliasesExpandidos);

            $tiposAliases[] = [
                'id' => (int) $tb['id'],
                'codigo' => (int) $tb['codigo'],
                'descricao' => $desc,
                'aliases' => $aliasesNorm,
                'aliases_originais' => array_map([$this, 'normalizar'], $aliases),
            ];
        }

        return $tiposAliases;
    }

    
    public function detectarTipo(string $texto, ?int $codigoDetectado, array $tiposAliases): array
    {
        $tipo = [
            'id' => 0,
            'codigo' => null,
            'descricao' => null,
            'alias_usado' => null
        ];

        $textoNorm = $this->normalizar($texto);

        
        if ($codigoDetectado !== null) {
            foreach ($tiposAliases as $tb) {
                if ((int) $tb['codigo'] === $codigoDetectado) {
                    $tipo = [
                        'id' => $tb['id'],
                        'codigo' => $tb['codigo'],
                        'descricao' => $tb['descricao'],
                        'alias_usado' => null
                    ];
                    return [$tipo, $texto];
                }
            }
        }

        
        $melhor = null;

        foreach ($tiposAliases as $tb) {
            foreach ($tb['aliases'] as $aliasNorm) {
                if ($aliasNorm !== '' && strpos($textoNorm, $aliasNorm) === 0) {
                    $len = strlen($aliasNorm);
                    if (!$melhor || $len > $melhor['len']) {
                        $melhor = [
                            'len' => $len,
                            'tb' => $tb,
                            'alias' => $aliasNorm
                        ];
                    }
                }
            }
        }

        if ($melhor) {
            $tipo = [
                'id' => $melhor['tb']['id'],
                'codigo' => $melhor['tb']['codigo'],
                'descricao' => $melhor['tb']['descricao'],
                'alias_usado' => $melhor['alias']
            ];
        }

        return [$tipo, trim($texto)];
    }

    
    public function extrairBenComplemento(
        string $texto,
        ?array $tipoAliases = null,
        ?array $aliasesOriginais = null,
        ?string $tipoDescricao = null
    ): array {
        $texto = trim($texto);

        
        if ($tipoDescricao && !empty($tipoDescricao) && $aliasesOriginais) {
            $tipoDescNorm = $this->normalizar($tipoDescricao);
            $textoNorm = $this->normalizar($texto);

            
            if (strpos($textoNorm, $tipoDescNorm) === 0) {
                $textoAposTipo = trim(substr($texto, strlen($tipoDescricao)));
                $textoAposTipo = preg_replace('/^[\s\-–—\/]+/u', '', $textoAposTipo);

                
                $aliasNoInicio = $this->temAliasNoInicio($textoAposTipo, $aliasesOriginais);
                if ($aliasNoInicio) {
                    $texto = $textoAposTipo;
                }
            }
        }

        
        if (preg_match('/^(.+?)\s+\-\s+(.+)$/u', $texto, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        
        if ($tipoAliases && !empty($tipoAliases)) {
            $resultado = $this->extrairBenPorAlias($texto, $tipoAliases, $aliasesOriginais);
            if ($resultado) {
                return $resultado;
            }
        }

        
        return [$texto, ''];
    }

    
    private function temAliasNoInicio(string $texto, array $aliasesOriginais): bool
    {
        $textoNorm = $this->normalizar($texto);

        foreach ($aliasesOriginais as $aliasOrig) {
            $aliasNorm = $this->normalizar($aliasOrig);
            $pattern = '/^' . preg_quote($aliasNorm, '/') . '\b/iu';

            if (preg_match($pattern, $textoNorm)) {
                return true;
            }
        }

        return false;
    }

    
    private function extrairBenPorAlias(string $texto, array $tipoAliases, ?array $aliasesOriginais): ?array
    {
        $textoNorm = $this->normalizar($texto);

        
        $aliasRepetido = $this->detectarAliasRepetido($textoNorm, $aliasesOriginais);

        
        $aliasesOrdenados = $this->ordenarAliases($tipoAliases, $aliasRepetido);

        
        foreach ($aliasesOrdenados as $aliasNorm) {
            if ($aliasNorm === '') {
                continue;
            }

            $variacoesAlias = $this->gerarVariacoes($aliasNorm);

            foreach ($variacoesAlias as $variacao) {
                $resultado = $this->extrairBenPorVariacao($texto, $textoNorm, $variacao, $aliasesOrdenados);

                if ($resultado) {
                    return $resultado;
                }
            }
        }

        return null;
    }

    
    private function detectarAliasRepetido(string $textoNorm, ?array $aliasesOriginais): ?array
    {
        if (!$aliasesOriginais) {
            return null;
        }

        $maxRepeticoes = 0;
        $aliasRepetido = null;

        foreach ($aliasesOriginais as $aliasOrig) {
            $pattern = '/\b' . preg_quote($aliasOrig, '/') . '\b/iu';
            $count = preg_match_all($pattern, $textoNorm);

            if ($count > $maxRepeticoes) {
                $maxRepeticoes = $count;
                $aliasRepetido = $aliasOrig;
            }
        }

        return $maxRepeticoes > 1 ? [$aliasRepetido, $maxRepeticoes] : null;
    }

    
    private function ordenarAliases(array $tipoAliases, ?array $aliasRepetido): array
    {
        $aliasesOrdenados = $tipoAliases;

        usort($aliasesOrdenados, function ($a, $b) use ($aliasRepetido) {
            if ($aliasRepetido && $aliasRepetido[1] > 1) {
                if ($a === $aliasRepetido[0]) {
                    return -1;
                }
                if ($b === $aliasRepetido[0]) {
                    return 1;
                }
            }
            return strlen($b) - strlen($a);
        });

        return $aliasesOrdenados;
    }

    
    private function extrairBenPorVariacao(
        string $texto,
        string $textoNorm,
        string $variacao,
        array $aliasesOrdenados
    ): ?array {
        $pattern = '/^(' . preg_quote($variacao, '/') . ')(\s|$)/iu';

        if (!preg_match($pattern, $textoNorm, $m)) {
            return null;
        }

        
        $matchLen = mb_strlen($m[1]);
        $posOrig = $this->encontrarPosicaoOriginal($texto, $matchLen);

        $ben = trim(mb_substr($texto, 0, $posOrig));
        $resto = trim(mb_substr($texto, $posOrig));

        
        $resto = preg_replace('/^[\s\-–—\/]+/u', '', $resto);

        
        $resto = $this->removerAliasesSequenciais($resto, $aliasesOrdenados);

        return [$ben, $resto];
    }

    
    private function encontrarPosicaoOriginal(string $texto, int $matchLen): int
    {
        $acumuladoNorm = '';
        $posOrig = 0;
        $textoLen = mb_strlen($texto);

        while (mb_strlen($acumuladoNorm) < $matchLen && $posOrig < $textoLen) {
            $char = mb_substr($texto, $posOrig, 1);
            $charNorm = $this->normalizarChar($char);
            $acumuladoNorm .= $charNorm;
            $posOrig++;
        }

        return $posOrig;
    }

    
    private function removerAliasesSequenciais(string $resto, array $aliasesOrdenados): string
    {
        $removeuAlgo = true;

        while ($removeuAlgo && $resto !== '') {
            $removeuAlgo = false;
            $restoNorm = $this->normalizar($resto);

            foreach ($aliasesOrdenados as $outroAlias) {
                if ($outroAlias === '') {
                    continue;
                }

                $variacoesOutro = $this->gerarVariacoes($outroAlias);

                foreach ($variacoesOutro as $varOutro) {
                    $patternOutro = '/^(' . preg_quote($varOutro, '/') . ')(\s|$)/iu';

                    if (preg_match($patternOutro, $restoNorm, $m2)) {
                        $matchLen2 = mb_strlen($m2[1]);
                        $posOrig2 = $this->encontrarPosicaoOriginal($resto, $matchLen2);

                        $resto = trim(mb_substr($resto, $posOrig2));
                        $resto = preg_replace('/^[\s\-–—\/]+/u', '', $resto);
                        $removeuAlgo = true;
                        break 2;
                    }
                }
            }
        }

        return $resto;
    }

    
    public function removerBenDoComplemento(string $ben, string $complemento): string
    {
        if ($ben === '' || $complemento === '') {
            return $complemento;
        }

        $benQuoted = preg_quote($ben, '/');
        $complemento = preg_replace('/^' . $benQuoted . '(\s+|\/|\-|:)+/u', '', $complemento);

        return trim(preg_replace('/\s+/', ' ', $complemento));
    }

    
    public function aplicarSinonimos(string $ben, string $complemento, ?string $tipoDesc): array
    {
        $benNorm = $this->normalizar($ben);
        $compNorm = $this->normalizar($complemento);
        $tipoNorm = $this->normalizar((string) $tipoDesc);

        
        if (!empty($this->config['synonyms'])) {
            foreach ($this->config['synonyms'] as $tipoKey => $map) {
                if ($this->normalizar($tipoKey) === $tipoNorm) {
                    foreach ($map as $from => $to) {
                        if (strpos($compNorm, $this->normalizar($from)) !== false) {
                            $ben = $to;
                            break 2;
                        }
                    }
                }
            }
        }

        
        if (!empty($this->config['global_synonyms'])) {
            foreach ($this->config['global_synonyms'] as $from => $to) {
                if (strpos($compNorm, $this->normalizar($from)) !== false) {
                    $ben = $to;
                    break;
                }
            }
        }

        
        $complemento = $this->removerBenDoComplemento(strtoupper($ben), strtoupper($complemento));

        return [$ben, $complemento];
    }

    
    public function forcarBenEmAliases(string $ben, ?string $tipoDesc, ?string $aliasUsado = null): string
    {
        $tokens = array_map('trim', preg_split('/\s*\/\s*/', (string) $tipoDesc));
        $tokensUpper = array_map('strtoupper', $tokens);
        $tokensNorm = array_map([$this, 'normalizar'], $tokens);
        $benNorm = $this->normalizar($ben);

        
        foreach ($tokensNorm as $i => $tNorm) {
            if ($tNorm !== '' && $tNorm === $benNorm) {
                return $tokensUpper[$i];
            }
        }

        
        if (!empty($aliasUsado)) {
            $aliasNorm = $this->normalizar($aliasUsado);
            foreach ($tokensNorm as $i => $tNorm) {
                if ($tNorm !== '' && $tNorm === $aliasNorm) {
                    return $tokensUpper[$i];
                }
            }
        }

        
        foreach ($tokensUpper as $tok) {
            if (trim($tok) !== '') {
                return strtoupper($tok);
            }
        }

        return strtoupper($ben);
    }

    
    public function montarDescricao(
        int $qtd,
        ?int $tipoCodigo,
        ?string $tipoDesc,
        string $ben,
        string $comp,
        string $dep
    ): string {
        $brackets = '?';
        if (!empty($tipoCodigo) && !empty($tipoDesc)) {
            $brackets = sprintf('%d - %s', $tipoCodigo, strtoupper($tipoDesc));
        }

        $ben = strtoupper(trim($ben));
        $comp = strtoupper(trim($comp));
        $dep = strtoupper(trim($dep));

        $desc = sprintf('%dx [%s]', $qtd, $brackets);

        if ($ben !== '') {
            $desc .= ' ' . $ben;
            if ($comp !== '') {
                $desc .= ' - ' . $comp;
            }
        } else {
            if ($comp !== '') {
                $desc .= ' ' . $comp;
            } else {
                $desc .= ' SEM DESCRICAO';
            }
        }

        if ($dep !== '') {
            $desc .= ' (' . $dep . ')';
        }

        return $desc;
    }
}
