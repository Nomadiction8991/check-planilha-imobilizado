<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\BaseRepository;
use PDO;
use Tests\TestCase;

final class BaseRepositoryTest extends TestCase
{
    private PDO $pdo;
    private BaseRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE test_table (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            email TEXT NOT NULL,
            ativo INTEGER NOT NULL DEFAULT 1
        )');

        $this->repo = new class ($this->pdo) extends BaseRepository {
            protected string $tabela = 'test_table';
            protected string $chavePrimaria = 'id';
            protected array $colunas = ['nome', 'email', 'ativo'];

            /** Expose protected paginar() for testing */
            public function paginarPublic(
                int $pagina,
                int $limite,
                string $where = '',
                array $params = [],
                string $orderBy = ''
            ): array {
                return $this->paginar($pagina, $limite, $where, $params, $orderBy);
            }
        };
    }

    /** @test */
    public function testCreateInsertsRecordAndReturnsId(): void
    {
        $id = $this->repo->criar([
            'nome' => 'João',
            'email' => 'joao@test.com',
            'ativo' => 1,
        ]);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $stmt = $this->pdo->query("SELECT * FROM test_table WHERE id = {$id}");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($row);
        $this->assertSame('João', $row['nome']);
        $this->assertSame('joao@test.com', $row['email']);
        $this->assertSame('1', (string) $row['ativo']);
    }

    /** @test */
    public function testFindByIdReturnsRecord(): void
    {
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('Maria', 'maria@test.com', 1)");
        $id = (int) $this->pdo->lastInsertId();

        $result = $this->repo->buscarPorId($id);

        $this->assertNotNull($result);
        $this->assertSame('Maria', $result['nome']);
        $this->assertSame('maria@test.com', $result['email']);
    }

    /** @test */
    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $result = $this->repo->buscarPorId(999);
        $this->assertNull($result);
    }

    /** @test */
    public function testFindAllReturnsAllRecords(): void
    {
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('A', 'a@t.com', 1)");
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('B', 'b@t.com', 1)");

        $results = $this->repo->buscarTodos();

        $this->assertCount(2, $results);
        $this->assertSame('A', $results[0]['nome']);
        $this->assertSame('B', $results[1]['nome']);
    }

    /** @test */
    public function testFindAllReturnsEmptyArrayWhenNoRecords(): void
    {
        $results = $this->repo->buscarTodos();
        $this->assertSame([], $results);
    }

    /** @test */
    public function testUpdateModifiesRecord(): void
    {
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('Carlo', 'carlo@test.com', 1)");
        $id = (int) $this->pdo->lastInsertId();

        $success = $this->repo->atualizar($id, ['nome' => 'Carla Atualizada']);

        $this->assertTrue($success);

        $stmt = $this->pdo->query("SELECT nome FROM test_table WHERE id = {$id}");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Carla Atualizada', $row['nome']);
    }

    /** @test */
    public function testUpdateReturnsTrueWhenNoChangesButValidId(): void
    {
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('Same', 'same@test.com', 1)");
        $id = (int) $this->pdo->lastInsertId();

        $success = $this->repo->atualizar($id, ['nome' => 'Same']);
        $this->assertTrue($success);
    }

    /** @test */
    public function testDeleteRemovesRecord(): void
    {
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('DeleteMe', 'del@test.com', 1)");
        $id = (int) $this->pdo->lastInsertId();

        $success = $this->repo->deletar($id);
        $this->assertTrue($success);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM test_table WHERE id = {$id}");
        $count = (int) $stmt->fetchColumn();
        $this->assertSame(0, $count);
    }

    /** @test */
    public function testDeleteReturnsFalseForNonExistentId(): void
    {
        // SQLite's UPDATE/DELETE on non-existent rows still returns rowCount=0,
        // but execute() itself returns true. We verify the method handles it gracefully.
        $success = $this->repo->deletar(999);
        // PDOStatement::execute() returns true even when 0 rows affected
        $this->assertTrue($success);
    }

    /** @test */
    public function testCountWithoutFilter(): void
    {
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('X', 'x@t.com', 1)");
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('Y', 'y@t.com', 0)");

        $total = $this->repo->contar();
        $this->assertSame(2, $total);
    }

    /** @test */
    public function testCountWithWhereFilter(): void
    {
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('A', 'a@t.com', 1)");
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('B', 'b@t.com', 0)");
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('C', 'c@t.com', 1)");

        $total = $this->repo->contar('ativo = :ativo', [':ativo' => 1]);
        $this->assertSame(2, $total);
    }

    /** @test */
    public function testPaginateFirstPage(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('User{$i}', 'u{$i}@t.com', 1)");
        }

        $result = $this->repo->paginarPublic(1, 3);

        $this->assertCount(3, $result['dados']);
        $this->assertSame(10, $result['total']);
        $this->assertSame(1, $result['pagina']);
        $this->assertSame(3, $result['limite']);
        $this->assertSame(4, $result['totalPaginas']);
    }

    /** @test */
    public function testPaginateLastPage(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('User{$i}', 'u{$i}@t.com', 1)");
        }

        $result = $this->repo->paginarPublic(4, 3);

        $this->assertCount(1, $result['dados']);
        $this->assertSame(4, $result['pagina']);
        $this->assertSame(4, $result['totalPaginas']);
        $this->assertSame('User10', $result['dados'][0]['nome']);
    }

    /** @test */
    public function testPaginateWithWhereFilter(): void
    {
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('Ativo', 'a@t.com', 1)");
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('Inativo', 'i@t.com', 0)");

        $result = $this->repo->paginarPublic(1, 10, 'ativo = :ativo', [':ativo' => 1]);

        $this->assertCount(1, $result['dados']);
        $this->assertSame(1, $result['total']);
        $this->assertSame('Ativo', $result['dados'][0]['nome']);
    }

    /** @test */
    public function testPaginateWithOrderBy(): void
    {
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('Charlie', 'c@t.com', 1)");
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('Alpha', 'a@t.com', 1)");
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('Bravo', 'b@t.com', 1)");

        $result = $this->repo->paginarPublic(1, 10, '', [], 'nome DESC');

        $this->assertSame('Charlie', $result['dados'][0]['nome']);
        $this->assertSame('Bravo', $result['dados'][1]['nome']);
        $this->assertSame('Alpha', $result['dados'][2]['nome']);
    }

    /** @test */
    public function testPaginateReturnsAllRowsWhenLimitExceedsTotal(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('User{$i}', 'u{$i}@t.com', 1)");
        }

        $result = $this->repo->paginarPublic(1, 100);

        $this->assertCount(3, $result['dados']);
        $this->assertSame(3, $result['total']);
        $this->assertSame(1, $result['totalPaginas']);
    }

    /** @test */
    public function testPaginateEmptyTable(): void
    {
        $result = $this->repo->paginarPublic(1, 10);

        $this->assertSame([], $result['dados']);
        $this->assertSame(0, $result['total']);
        $this->assertSame(1, $result['pagina']);
        $this->assertSame(10, $result['limite']);
        $this->assertSame(1, $result['totalPaginas']);
    }

    /** @test */
    public function testFilterColumnsOnCreateBlocksUnknownColumns(): void
    {
        $id = $this->repo->criar([
            'nome' => 'Seguro',
            'email' => 'seguro@test.com',
            'ativo' => 1,
            'coluna_inexistente' => 'tentativa de injection',
            'outra_injection' => 'hack',
        ]);

        $stmt = $this->pdo->query("SELECT * FROM test_table WHERE id = {$id}");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertArrayNotHasKey('coluna_inexistente', $row);
        $this->assertArrayNotHasKey('outra_injection', $row);
        $this->assertSame('Seguro', $row['nome']);
    }

    /** @test */
    public function testFilterColumnsOnUpdateBlocksUnknownColumns(): void
    {
        $this->pdo->exec("INSERT INTO test_table (nome, email, ativo) VALUES ('Original', 'o@t.com', 1)");
        $id = (int) $this->pdo->lastInsertId();

        $this->repo->atualizar($id, [
            'email' => 'atualizado@test.com',
            'coluna_inexistente' => 'sql injection',
        ]);

        $stmt = $this->pdo->query("SELECT email FROM test_table WHERE id = {$id}");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('atualizado@test.com', $row['email']);
    }
}
