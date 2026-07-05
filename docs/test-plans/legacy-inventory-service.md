# Test Plan: LegacyInventoryService

## Overview

**Class:** `App\Services\LegacyInventoryService`  
**Interface:** `App\Contracts\LegacyInventoryServiceInterface`  
**File:** `app/Services/LegacyInventoryService.php`  
**Purpose:** Build a snapshot of the legacy system health — database connectivity, architecture file counts, and per-module record summaries.

The service has **one public method** (`buildSnapshot()`) and **four private helpers** (`probeDatabase`, `collectArchitectureCounts`, `collectModuleSummaries`, `countModelRecords`). Tests should exercise the public method across all meaningful states of its internals.

---

## 1. Dependencies & Mocking Strategy

| Dependency | How it's used | Mocking approach |
|---|---|---|
| `config()` global helper | `legacy.root_path`, `legacy.public_url`, `legacy.paths`, `legacy.modules` | `Config::shouldReceive('get')->with(...)->andReturn(...)` (Laravel Config facade) |
| `DB::connection()` | `getPdo()`, `getDriverName()`, `getDatabaseName()` | `DB::shouldReceive('connection')->once()->andReturn($mockConnection)` with a mock that exposes `getPdo()`, `getDriverName()`, `getDatabaseName()` |
| `File::allFiles()` | Count files in each architecture path | `File::shouldReceive('allFiles')->once()->with($fullPath)->andReturn([...])` |
| Eloquent models | Called dynamically via `$modelClass::query()` | Create a real test model that extends `Model` with `$table` set, or use `Mockery::mock('alias:' . $class)` for the query builder chain. **Preferred:** use an actual Eloquent model from the app (e.g. `Usuario::class`) with SQLite in-memory for integration, OR mock the query builder chain for pure unit isolation. |
| `is_a()` check | Validates model class extends `Illuminate\Database\Eloquent\Model` | Test via passing a non-Model class name e.g. `stdClass::class` → must return `null` |

---

## 2. Test Cases

### 2.1 `buildSnapshot()` — happy path

**Test:** `test_build_snapshot_returns_complete_snapshot`  
**Validates:** Full end-to-end execution returns a `LegacyInventorySnapshot` with all fields populated.

**Setup:**
- `config('legacy.root_path')` → `/var/www/legacy`
- `config('legacy.public_url')` → `http://legacy.test`
- `config('legacy.paths')` → `['controllers' => 'app/Controllers', 'views' => 'app/views']`
- `config('legacy.modules')` → 2 modules (one with model + scope, one without scope)
- `DB::connection()->getPdo()` — succeeds (no exception)
- `DB::connection()->getDriverName()` → `'pgsql'`
- `DB::connection()->getDatabaseName()` → `'checkplanilha'`
- `File::allFiles('/var/www/legacy/app/Controllers')` → `[SplFileInfo, SplFileInfo]` (2 files)
- `File::allFiles('/var/www/legacy/app/views')` → `[SplFileInfo]` (1 file)
- Mock a model query returning count=42

**Assertions:**
- `assertInstanceOf(LegacyInventorySnapshot::class, $result)`
- `$result->legacyRootPath === '/var/www/legacy'`
- `$result->legacyPublicUrl === 'http://legacy.test'`
- `$result->databaseReachable === true`
- `$result->databaseDriver === 'pgsql'`
- `$result->databaseName === 'checkplanilha'`
- `$result->databaseError === null`
- `$result->architectureCounts === ['controllers' => 2, 'views' => 1]`
- `count($result->modules) === 2`
- `$result->modules[0]->records === 42`

---

### 2.2 Database unreachable

**Test:** `test_build_snapshot_when_database_unreachable_returns_graceful_response`  
**Validates:** When `DB::connection()->getPdo()` throws, the snapshot reflects the failure and all module records are `null`.

**Setup:**
- `DB::connection()->getPdo()` throws a `PDOException('Connection refused')`
- Config: `legacy.paths` = 1 path, `legacy.modules` = 2 modules (both with models)
- `File::allFiles()` returns 3 files

**Assertions:**
- `$result->databaseReachable === false`
- `$result->databaseDriver === null`
- `$result->databaseName === null`
- `$result->databaseError === 'Connection refused'`
- Every module in `$result->modules` has `records === null`

---

### 2.3 Database throws generic `Throwable`

**Test:** `test_build_snapshot_catches_generic_exceptions_from_database_probe`  
**Validates:** Any `Throwable` (not just PDO exceptions) is caught gracefully.

**Setup:**
- `DB::connection()->getPdo()` throws a `RuntimeException('Something went wrong')`

**Assertions:**
- `$result->databaseReachable === false`
- `$result->databaseError === 'Something went wrong'`

---

### 2.4 Empty paths list

**Test:** `test_build_snapshot_with_empty_paths_config`  
**Validates:** `architectureCounts` is empty when config returns `[]`.

**Setup:**
- `config('legacy.paths')` → `[]`
- DB reachable, `config('legacy.modules')` → 1 module

**Assertions:**
- `$result->architectureCounts === []`
- `File::allFiles()` is never called (verify via `shouldNotReceive`)

---

### 2.5 Directory does not exist

**Test:** `test_build_snapshot_with_missing_directory_returns_zero_count`  
**Validates:** When a configured path directory does not exist, count is 0 and `File::allFiles()` is not called for that path.

**Setup:**
- `config('legacy.paths')` → `['controllers' => 'app/Controllers', 'views' => 'app/MissingDir']`
- `is_dir()` returns true for `controllers` path, false for `views` path
- `File::allFiles()` called only for `controllers`

**Assertions:**
- `$result->architectureCounts['controllers'] === N` (N = files in controllers)
- `$result->architectureCounts['views'] === 0`
- `File::allFiles()` called exactly once (only for existing dir)

---

### 2.6 Empty modules list

**Test:** `test_build_snapshot_with_empty_modules_config`  
**Validates:** `modules` array is empty when config returns `[]`.

**Setup:**
- `config('legacy.modules')` → `[]`
- DB reachable

**Assertions:**
- `$result->modules === []`

---

### 2.7 Module model not a valid Eloquent model class

**Test:** `test_build_snapshot_with_invalid_model_class_returns_null_records`  
**Validates:** When a module's `model` string doesn't extend `Illuminate\Database\Eloquent\Model`, `records` is `null`.

**Setup:**
- `config('legacy.modules')` → 1 module with `'model' => stdClass::class`
- DB reachable

**Assertions:**
- `$result->modules[0]->records === null`

---

### 2.8 Model query throws exception

**Test:** `test_build_snapshot_when_model_query_throws_exception_returns_null_records`  
**Validates:** If the model's query builder throws a `Throwable`, records gracefully returns `null` instead of propagating the exception.

**Setup:**
- `config('legacy.modules')` → 1 module with a valid Model class
- `$modelClass::query()` throws a `RuntimeException`

**Assertions:**
- `$result->modules[0]->records === null`

---

### 2.9 Module with scope applied

**Test:** `test_build_snapshot_with_scoped_module`  
**Validates:** When a module defines a `scope` and the scope method exists on the model, the scope is applied before counting.

**Setup:**
- `config('legacy.modules')` → 1 module with `'model' => SomeModel::class, 'scope' => 'active'`
- The model has a `scopeActive()` method that modifies the query
- The scope is called before `->count()`

**Assertions:**
- `$result->modules[0]->records === N` (count after scope applied)

---

### 2.10 Module with scope that does not exist on model

**Test:** `test_build_snapshot_with_nonexistent_scope_skips_scope_gracefully`  
**Validates:** When a module defines a `scope` but the model doesn't have the corresponding `scope*` method, the scope is skipped and count runs on the base query.

**Setup:**
- `config('legacy.modules')` → 1 module with `'model' => SomeModel::class, 'scope' => 'nonexistentScope'`
- The model does NOT have `scopeNonexistentScope()`

**Assertions:**
- `$result->modules[0]->records === N` (count without scope applied)
- No error/exception is thrown

---

### 2.11 Module without a model

**Test:** `test_build_snapshot_with_module_without_model_returns_null_records_regardless_of_db`  
**Validates:** When a module config has no `'model'` key, `records` is `null` even when DB is reachable.

**Setup:**
- `config('legacy.modules')` → 1 module without `'model'` key (like the `reports` module)
- DB reachable

**Assertions:**
- `$result->modules[0]->records === null`

---

### 2.12 Module with optional default values

**Test:** `test_build_snapshot_uses_defaults_for_optional_module_fields`  
**Validates:** When module config omits `category` and `tone`, defaults `'Estrutura'` and `'structure'` are used.

**Setup:**
- `config('legacy.modules')` → 1 module with no `category` or `tone` keys

**Assertions:**
- `$result->modules[0]->category === 'Estrutura'`
- `$result->modules[0]->tone === 'structure'`

---

### 2.13 Config with trailing slash in `public_url`

**Test:** `test_build_snapshot_trims_trailing_slash_from_public_url`  
**Validates:** Trailing `/` is stripped from `config('legacy.public_url')`.

**Setup:**
- `config('legacy.public_url')` → `'http://legacy.test/'`
- All other config minimal

**Assertions:**
- `$result->legacyPublicUrl === 'http://legacy.test'` (no trailing slash)

---

### 2.14 Empty `root_path`

**Test:** `test_build_snapshot_with_empty_root_path`  
**Validates:** Code handles empty root_path gracefully (still works, just relative paths).

**Setup:**
- `config('legacy.root_path')` → `''`
- `config('legacy.paths')` → `['controllers' => 'app/Controllers']`
- `is_dir('app/Controllers')` returns true (or false)

**Assertions:**
- Snapshot is returned without exception
- Architecture count reflects directory at `app/Controllers`

---

### 2.15 Database probe returns null driver/name

**Test:** `test_probe_database_throws_non_pdo_exception` (if testing private helper directly via reflection)  
**Validates:** Different exception types still set error message and mark unreachable.

**Setup:**
- `DB::connection()->getPdo()` throws `ErrorException('Call to a member function...')`

**Assertions:**
- `$result->databaseReachable === false`
- `$result->databaseError` non-null

---

## 3. Mocks Needed Summary

| Mock | Method(s) | Count |
|---|---|---|
| `Config` facade | `shouldReceive('get')->with('legacy.root_path')` | 1 |
| `Config` facade | `shouldReceive('get')->with('legacy.public_url')` | 1 |
| `Config` facade | `shouldReceive('get')->with('legacy.paths')` | 1 |
| `Config` facade | `shouldReceive('get')->with('legacy.modules')` | 1 |
| `DB` facade | `shouldReceive('connection')->andReturn($mockConn)` | 1 |
| `$mockConn` stdClass | `->getPdo()` (no exception for happy path) | 1 |
| `$mockConn` stdClass | `->getDriverName()` | 1 |
| `$mockConn` stdClass | `->getDatabaseName()` | 1 |
| `File` facade | `shouldReceive('allFiles')` with `$fullPath` | once per existing path |
| Model class | Static `::query()` → returns `$queryBuilder` mock | once per module with model |
| `$queryBuilder` mock | `->getModel()` → returns model instance | for scope check |
| `$queryBuilder` mock | `->{scope}()` (dynamic) **or** `shouldReceive('scopeName')` | optional |
| `$queryBuilder` mock | `->count()` | 1 |

---

## 4. Testing Approach Options

### Option A: Pure Unit (preferred for this service)
Mock all facades (`Config`, `DB`, `File`) and the model query builder chain. Test `buildSnapshot()` directly and verify the returned `LegacyInventorySnapshot` shape. This gives complete isolation and speed.

- **Pros:** Fast, no DB needed, no model setup
- **Cons:** Heavier mocking of Eloquent query builder chain

### Option B: Integration-light
Use SQLite in-memory (`:memory:`) with real Eloquent model instances for the `countModelRecords` path. Mock only `Config`, `DB::connection()->getPdo()` (for probe), and `File::allFiles()` (for architecture counts).

- **Pros:** More realistic module record counts, less fragile mocking
- **Cons:** Need to create test models/migrations, slightly slower

### Recommendation
**Option A** for most tests (pure, fast, isolated), with **Option B** for the `countModelRecords` path if there are concerns about Eloquent compatibility.

---

## 5. File Structure

```
tests/Unit/Services/LegacyInventoryServiceTest.php
```

Use `Tests\TestCase` (Laravel base) for facade mocking support. Follow the project convention of `final class`, `declare(strict_types=1)`.

---

## 6. Notes

- The service has **no constructor injection** — it uses `config()`, `DB::`, and `File::` statically. All dependencies must be mocked via Laravel facades.
- `File::allFiles()` returns an array of `SplFileInfo` objects; the test just needs an array with the right count.
- The `countModelRecords` path calls `$modelClass::query()` which is a static call on an unknown class — either mock the class with an alias, or use a test model with `$table` and SQLite.
- `method_exists($query->getModel(), 'scope' . ucfirst($scope))` — the `getModel()` returns the underlying Eloquent model instance for scope detection. Mock this chain carefully.
