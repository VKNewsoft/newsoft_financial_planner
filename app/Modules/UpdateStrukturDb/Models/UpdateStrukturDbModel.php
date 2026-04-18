<?php

namespace App\Modules\UpdateStrukturDb\Models;

class UpdateStrukturDbModel extends \App\Modules\Common\Models\BaseModel
{
	private function getSqlFilePath(): string
	{
		return APPPATH . 'Database/newsoft_base.sql';
	}

	private function getSqlContent(): string
	{
		$path = $this->getSqlFilePath();
		return is_file($path) ? (string) file_get_contents($path) : '';
	}

	private function splitSqlTuples(string $valuesPart): array
	{
		$result = [];
		$buffer = '';
		$depth = 0;
		$inQuote = false;
		$length = strlen($valuesPart);

		for ($i = 0; $i < $length; $i++) {
			$char = $valuesPart[$i];
			$next = $i + 1 < $length ? $valuesPart[$i + 1] : '';

			if ($char === "'" && $inQuote && $next === "'") {
				$buffer .= "''";
				$i++;
				continue;
			}

			if ($char === "'") {
				$inQuote = !$inQuote;
				$buffer .= $char;
				continue;
			}

			if (!$inQuote && $char === '(') {
				if ($depth > 0) {
					$buffer .= $char;
				}
				$depth++;
				continue;
			}

			if (!$inQuote && $char === ')') {
				$depth--;
				if ($depth === 0) {
					$result[] = $buffer;
					$buffer = '';
					continue;
				}
			}

			if ($depth > 0) {
				$buffer .= $char;
			}
		}

		return $result;
	}

	private function splitSqlValues(string $tuple): array
	{
		$result = [];
		$buffer = '';
		$inQuote = false;
		$length = strlen($tuple);

		for ($i = 0; $i < $length; $i++) {
			$char = $tuple[$i];
			$next = $i + 1 < $length ? $tuple[$i + 1] : '';

			if ($char === "'" && $inQuote && $next === "'") {
				$buffer .= "''";
				$i++;
				continue;
			}

			if ($char === "'") {
				$inQuote = !$inQuote;
				$buffer .= $char;
				continue;
			}

			if (!$inQuote && $char === ',') {
				$result[] = trim($buffer);
				$buffer = '';
				continue;
			}

			$buffer .= $char;
		}

		if ($buffer !== '') {
			$result[] = trim($buffer);
		}

		return $result;
	}

	private function normalizeSqlValue(string $value)
	{
		$value = trim($value);
		if (strtoupper($value) === 'NULL') {
			return null;
		}

		if (preg_match('/^\'(.*)\'$/s', $value, $matches)) {
			return str_replace("''", "'", $matches[1]);
		}

		if (is_numeric($value)) {
			return strpos($value, '.') !== false ? (float) $value : (int) $value;
		}

		return $value;
	}

	private function normalizeSqlDefinition(?string $value): string
	{
		$value = $value ?? '';
		$value = preg_replace('/\s+/', ' ', trim($value));
		return strtoupper($value);
	}

	private function parseInsertRows(string $table): array
	{
		$sql = $this->getSqlContent();
		$pattern = '/insert\s+into\s+`' . preg_quote($table, '/') . '`\((.*?)\)\s+values\s*(.*?);/is';
		preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER);
		$rows = [];

		foreach ($matches as $match) {
			$columns = array_map(static function ($column) {
				return trim($column, " \t\n\r\0\x0B`");
			}, explode(',', $match[1]));

			foreach ($this->splitSqlTuples($match[2]) as $tuple) {
				$values = $this->splitSqlValues($tuple);
				$row = [];
				foreach ($columns as $index => $column) {
					$row[$column] = array_key_exists($index, $values) ? $this->normalizeSqlValue($values[$index]) : null;
				}
				$rows[] = $row;
			}
		}

		return $rows;
	}

	private function parseCreateTableSql(string $sql): array
	{
		$pattern = '/CREATE TABLE `(.*?)` \((.*?)\)\s*ENGINE=.*?;/is';
		if (!preg_match($pattern, $sql, $match)) {
			return [];
		}

		$table = $match[1];
		$body = trim($match[2]);
		$definitions = preg_split('/\r\n|\r|\n/', $body);
		$item = [
			'table' => $table,
			'create_sql' => trim($match[0]),
			'columns' => [],
			'primary' => '',
			'indexes' => [],
			'constraints' => [],
		];

		foreach ($definitions as $line) {
			$line = trim(rtrim($line, ','));
			if ($line === '') {
				continue;
			}

			if (preg_match('/^`([^`]+)`\s+/i', $line, $columnMatch)) {
				$item['columns'][$columnMatch[1]] = $line;
				continue;
			}

			if (stripos($line, 'PRIMARY KEY') === 0) {
				$item['primary'] = $line;
				continue;
			}

			if (preg_match('/^(UNIQUE KEY|KEY)\s+`([^`]+)`/i', $line, $indexMatch)) {
				$item['indexes'][$indexMatch[2]] = $line;
				continue;
			}

			if (preg_match('/^CONSTRAINT\s+`([^`]+)`/i', $line, $constraintMatch)) {
				$item['constraints'][$constraintMatch[1]] = $line;
			}
		}

		return $item;
	}

	private function parseSchemaDefinitions(): array
	{
		$sql = $this->getSqlContent();
		$pattern = '/CREATE TABLE `(.*?)` \((.*?)\)\s*ENGINE=.*?;/is';
		preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER);
		$result = [];

		foreach ($matches as $match) {
			$parsed = $this->parseCreateTableSql($match[0]);
			if ($parsed) {
				$result[$parsed['table']] = $parsed;
			}
		}

		return $result;
	}

	private function getCurrentSchemaDefinitions(): array
	{
		$tables = $this->db->listTables();
		$result = [];
		foreach ($tables as $table) {
			$row = $this->db->query('SHOW CREATE TABLE `' . $table . '`')->getRowArray();
			if (!empty($row['Create Table'])) {
				$parsed = $this->parseCreateTableSql($row['Create Table'] . ';');
				if ($parsed) {
					$result[$table] = $parsed;
				}
			}
		}
		return $result;
	}

	private function makeDiffItem(string $scope, string $group, string $table, string $name, ?string $left, ?string $right, string $action, string $sql = ''): array
	{
		return [
			'scope' => $scope,
			'group' => $group,
			'table' => $table,
			'name' => $name,
			'left' => $left,
			'right' => $right,
			'action' => $action,
			'sql' => $sql,
		];
	}

	private function compareSchema(): array
	{
		$current = $this->getCurrentSchemaDefinitions();
		$target = $this->parseSchemaDefinitions();
		$tables = array_values(array_unique(array_merge(array_keys($current), array_keys($target))));
		sort($tables);

		$items = [];
		$changes = [];

		foreach ($tables as $table) {
			$currentTable = $current[$table] ?? null;
			$targetTable = $target[$table] ?? null;

			if (!$currentTable && $targetTable) {
				$sql = $targetTable['create_sql'];
				$items[] = $this->makeDiffItem('schema', 'table', $table, $table, null, $sql, 'add', $sql);
				$changes[] = $items[array_key_last($items)];
				continue;
			}

			if (!$targetTable) {
				continue;
			}

			$columnNames = array_values(array_unique(array_merge(array_keys($currentTable['columns']), array_keys($targetTable['columns']))));
			sort($columnNames);
			foreach ($columnNames as $columnName) {
				$left = $currentTable['columns'][$columnName] ?? null;
				$right = $targetTable['columns'][$columnName] ?? null;
				if ($this->normalizeSqlDefinition($left) === $this->normalizeSqlDefinition($right)) {
					continue;
				}

				$action = !$left ? 'add' : (!$right ? 'missing_target' : 'modify');
				$sql = '';
				if ($right) {
					$sql = !$left
						? 'ALTER TABLE `' . $table . '` ADD COLUMN ' . $right
						: 'ALTER TABLE `' . $table . '` MODIFY COLUMN ' . $right;
				}

				$items[] = $this->makeDiffItem('schema', 'column', $table, $columnName, $left, $right, $action, $sql);
				if ($sql !== '') {
					$changes[] = $items[array_key_last($items)];
				}
			}

			$primaryLeft = $currentTable['primary'] ?: null;
			$primaryRight = $targetTable['primary'] ?: null;
			if ($this->normalizeSqlDefinition($primaryLeft) !== $this->normalizeSqlDefinition($primaryRight) && $primaryRight) {
				$action = $primaryLeft ? 'review' : 'add';
				$sql = $primaryLeft ? '' : 'ALTER TABLE `' . $table . '` ADD ' . $primaryRight;
				$items[] = $this->makeDiffItem('schema', 'primary', $table, 'PRIMARY', $primaryLeft, $primaryRight, $action, $sql);
				if ($sql !== '') {
					$changes[] = $items[array_key_last($items)];
				}
			}

			$indexNames = array_values(array_unique(array_merge(array_keys($currentTable['indexes']), array_keys($targetTable['indexes']))));
			sort($indexNames);
			foreach ($indexNames as $indexName) {
				$left = $currentTable['indexes'][$indexName] ?? null;
				$right = $targetTable['indexes'][$indexName] ?? null;
				if ($this->normalizeSqlDefinition($left) === $this->normalizeSqlDefinition($right)) {
					continue;
				}

				$action = !$left ? 'add' : (!$right ? 'missing_target' : 'review');
				$sql = !$left && $right ? 'ALTER TABLE `' . $table . '` ADD ' . $right : '';
				$items[] = $this->makeDiffItem('schema', 'index', $table, $indexName, $left, $right, $action, $sql);
				if ($sql !== '') {
					$changes[] = $items[array_key_last($items)];
				}
			}

			$constraintNames = array_values(array_unique(array_merge(array_keys($currentTable['constraints']), array_keys($targetTable['constraints']))));
			sort($constraintNames);
			foreach ($constraintNames as $constraintName) {
				$left = $currentTable['constraints'][$constraintName] ?? null;
				$right = $targetTable['constraints'][$constraintName] ?? null;
				if ($this->normalizeSqlDefinition($left) === $this->normalizeSqlDefinition($right)) {
					continue;
				}

				$action = !$left ? 'add' : (!$right ? 'missing_target' : 'review');
				$sql = !$left && $right ? 'ALTER TABLE `' . $table . '` ADD ' . $right : '';
				$items[] = $this->makeDiffItem('schema', 'constraint', $table, $constraintName, $left, $right, $action, $sql);
				if ($sql !== '') {
					$changes[] = $items[array_key_last($items)];
				}
			}
		}

		return ['items' => $items, 'changes' => $changes];
	}

	private function getExistingRows(string $table): array
	{
		return $this->db->table($table)->get()->getResultArray();
	}

	private function getCoreConfig(): array
	{
		return [
			'core_module' => [
				'key' => ['nama_module'],
				'id' => 'id_module',
				'fields' => ['judul_module', 'id_module_status', 'login', 'deskripsi'],
			],
			'core_menu_kategori' => [
				'key' => ['nama_kategori'],
				'id' => 'id_menu_kategori',
				'fields' => ['deskripsi', 'aktif', 'show_title', 'urut'],
			],
			'core_menu' => [
				'key' => ['nama_menu', 'url'],
				'id' => 'id_menu',
				'fields' => ['id_menu_kategori', 'class', 'id_module', 'id_parent', 'aktif', 'new', 'urut'],
			],
			'core_menu_role' => [
				'key' => ['id_menu', 'id_role'],
				'id' => null,
				'fields' => [],
			],
		];
	}

	private function getMenuKey($name, $url): string
	{
		return trim((string) $name) . '|' . trim((string) $url);
	}

	private function getRowKey(array $row, array $keys): string
	{
		$parts = [];
		foreach ($keys as $key) {
			$parts[] = (string) ($row[$key] ?? '');
		}
		return implode('|', $parts);
	}

	private function getRoleNameMap(): array
	{
		$map = [];
		foreach ($this->parseInsertRows('core_role') as $row) {
			$map[(int) $row['id_role']] = (string) $row['nama_role'];
		}
		return $map;
	}

	private function buildCoreSnapshots(): array
	{
		$roleNameMap = $this->getRoleNameMap();
		$sourceModules = $this->parseInsertRows('core_module');
		$currentModules = $this->getExistingRows('core_module');
		$sourceModuleIdByName = [];
		$currentModuleIdByName = [];
		foreach ($sourceModules as $row) {
			$sourceModuleIdByName[$row['nama_module']] = (int) $row['id_module'];
		}
		foreach ($currentModules as $row) {
			$currentModuleIdByName[$row['nama_module']] = (int) $row['id_module'];
		}

		$sourceCategories = $this->parseInsertRows('core_menu_kategori');
		$currentCategories = $this->getExistingRows('core_menu_kategori');
		$sourceCategoryIdByName = [];
		$currentCategoryIdByName = [];
		foreach ($sourceCategories as $row) {
			$sourceCategoryIdByName[$row['nama_kategori']] = (int) $row['id_menu_kategori'];
		}
		foreach ($currentCategories as $row) {
			$currentCategoryIdByName[$row['nama_kategori']] = (int) $row['id_menu_kategori'];
		}

		$sourceMenus = $this->parseInsertRows('core_menu');
		$currentMenus = $this->getExistingRows('core_menu');
		$sourceMenuKeyById = [];
		$currentMenuIdByKey = [];
		foreach ($sourceMenus as $row) {
			$sourceMenuKeyById[(int) $row['id_menu']] = $this->getMenuKey($row['nama_menu'], $row['url']);
		}
		foreach ($currentMenus as $row) {
			$currentMenuIdByKey[$this->getMenuKey($row['nama_menu'], $row['url'])] = (int) $row['id_menu'];
		}

		return compact(
			'roleNameMap',
			'sourceModuleIdByName',
			'currentModuleIdByName',
			'sourceCategoryIdByName',
			'currentCategoryIdByName',
			'sourceMenuKeyById',
			'currentMenuIdByKey'
		);
	}

	private function mapCoreRow(string $table, array $row, array $snapshots, string $mode): array
	{
		if ($table === 'core_menu') {
			if (!empty($row['id_module'])) {
				$name = array_search((int) $row['id_module'], $mode === 'target' ? $snapshots['sourceModuleIdByName'] : $snapshots['currentModuleIdByName'], true);
				$row['id_module'] = $name ?: $row['id_module'];
			}
			if (!empty($row['id_menu_kategori'])) {
				$name = array_search((int) $row['id_menu_kategori'], $mode === 'target' ? $snapshots['sourceCategoryIdByName'] : $snapshots['currentCategoryIdByName'], true);
				$row['id_menu_kategori'] = $name ?: $row['id_menu_kategori'];
			}
			if (!empty($row['id_parent'])) {
				$key = $mode === 'target'
					? ($snapshots['sourceMenuKeyById'][(int) $row['id_parent']] ?? $row['id_parent'])
					: (array_search((int) $row['id_parent'], $snapshots['currentMenuIdByKey'], true) ?: $row['id_parent']);
				$row['id_parent'] = $key;
			}
		}

		if ($table === 'core_menu_role') {
			$menuKey = $mode === 'target'
				? ($snapshots['sourceMenuKeyById'][(int) $row['id_menu']] ?? (string) $row['id_menu'])
				: (array_search((int) $row['id_menu'], $snapshots['currentMenuIdByKey'], true) ?: (string) $row['id_menu']);
			$roleName = $snapshots['roleNameMap'][(int) $row['id_role']] ?? (string) $row['id_role'];
			$row['id_menu'] = $menuKey;
			$row['id_role'] = $roleName;
		}

		return $row;
	}

	private function compareCoreData(): array
	{
		$configs = $this->getCoreConfig();
		$snapshots = $this->buildCoreSnapshots();
		$items = [];
		$changes = [];

		foreach ($configs as $table => $config) {
			$targetRows = [];
			foreach ($this->parseInsertRows($table) as $row) {
				$targetRows[$this->getRowKey($this->mapCoreRow($table, $row, $snapshots, 'target'), $config['key'])] = $this->mapCoreRow($table, $row, $snapshots, 'target');
			}

			$currentRows = [];
			foreach ($this->getExistingRows($table) as $row) {
				$currentRows[$this->getRowKey($this->mapCoreRow($table, $row, $snapshots, 'current'), $config['key'])] = $this->mapCoreRow($table, $row, $snapshots, 'current');
			}

			$keys = array_values(array_unique(array_merge(array_keys($currentRows), array_keys($targetRows))));
			sort($keys);

			foreach ($keys as $key) {
				$leftRow = $currentRows[$key] ?? null;
				$rightRow = $targetRows[$key] ?? null;
				$leftJson = $leftRow ? json_encode($leftRow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
				$rightJson = $rightRow ? json_encode($rightRow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

				if ($leftRow && $rightRow) {
					$changed = false;
					foreach ($config['fields'] as $field) {
						if (($leftRow[$field] ?? null) != ($rightRow[$field] ?? null)) {
							$changed = true;
							break;
						}
					}

					if ($changed) {
						$items[] = $this->makeDiffItem('core_data', $table, $table, $key, $leftJson, $rightJson, 'update');
						$changes[] = $items[array_key_last($items)];
					}
					continue;
				}

				if (!$leftRow && $rightRow) {
					$items[] = $this->makeDiffItem('core_data', $table, $table, $key, null, $rightJson, 'add');
					$changes[] = $items[array_key_last($items)];
				}
			}
		}

		return ['items' => $items, 'changes' => $changes];
	}

	public function getPreview(): array
	{
		$schema = $this->compareSchema();
		$core = $this->compareCoreData();

		return [
			'schema_items' => $schema['items'],
			'core_items' => $core['items'],
			'schema_changes' => $schema['changes'],
			'core_changes' => $core['changes'],
			'total_schema' => count($schema['changes']),
			'total_core' => count($core['changes']),
			'total_all' => count($schema['changes']) + count($core['changes']),
		];
	}

	private function syncCoreModules(): array
	{
		$messages = [];
		$current = [];
		foreach ($this->db->table('core_module')->get()->getResultArray() as $row) {
			$current[$row['nama_module']] = $row;
		}

		foreach ($this->parseInsertRows('core_module') as $row) {
			$data = $row;
			unset($data['id_module']);
			if (isset($current[$row['nama_module']])) {
				$this->db->table('core_module')->where('id_module', $current[$row['nama_module']]['id_module'])->update($data);
				$messages[] = 'Update module ' . $row['nama_module'];
			} else {
				$this->db->table('core_module')->insert($data);
				$messages[] = 'Insert module ' . $row['nama_module'];
			}
		}

		return $messages;
	}

	private function syncCoreMenuKategori(): array
	{
		$messages = [];
		$current = [];
		foreach ($this->db->table('core_menu_kategori')->get()->getResultArray() as $row) {
			$current[$row['nama_kategori']] = $row;
		}

		foreach ($this->parseInsertRows('core_menu_kategori') as $row) {
			$data = $row;
			unset($data['id_menu_kategori']);
			if (isset($current[$row['nama_kategori']])) {
				$this->db->table('core_menu_kategori')->where('id_menu_kategori', $current[$row['nama_kategori']]['id_menu_kategori'])->update($data);
				$messages[] = 'Update menu kategori ' . $row['nama_kategori'];
			} else {
				$this->db->table('core_menu_kategori')->insert($data);
				$messages[] = 'Insert menu kategori ' . $row['nama_kategori'];
			}
		}

		return $messages;
	}

	private function syncCoreMenu(): array
	{
		$messages = [];
		$currentModules = [];
		foreach ($this->db->table('core_module')->get()->getResultArray() as $row) {
			$currentModules[$row['nama_module']] = (int) $row['id_module'];
		}

		$currentCategories = [];
		foreach ($this->db->table('core_menu_kategori')->get()->getResultArray() as $row) {
			$currentCategories[$row['nama_kategori']] = (int) $row['id_menu_kategori'];
		}

		$currentMenus = [];
		foreach ($this->db->table('core_menu')->get()->getResultArray() as $row) {
			$currentMenus[$this->getMenuKey($row['nama_menu'], $row['url'])] = $row;
		}

		$sourceModules = [];
		foreach ($this->parseInsertRows('core_module') as $row) {
			$sourceModules[(int) $row['id_module']] = $row['nama_module'];
		}

		$sourceCategories = [];
		foreach ($this->parseInsertRows('core_menu_kategori') as $row) {
			$sourceCategories[(int) $row['id_menu_kategori']] = $row['nama_kategori'];
		}

		$sourceMenus = $this->parseInsertRows('core_menu');
		$sourceMenuById = [];
		foreach ($sourceMenus as $row) {
			$sourceMenuById[(int) $row['id_menu']] = $row;
		}

		foreach ($sourceMenus as $row) {
			$key = $this->getMenuKey($row['nama_menu'], $row['url']);
			$data = $row;
			unset($data['id_menu']);
			$data['id_module'] = !empty($row['id_module']) ? ($currentModules[$sourceModules[(int) $row['id_module']] ?? ''] ?? null) : null;
			$data['id_menu_kategori'] = !empty($row['id_menu_kategori']) ? ($currentCategories[$sourceCategories[(int) $row['id_menu_kategori']] ?? ''] ?? 0) : 0;
			$data['id_parent'] = null;
			if (!empty($row['id_parent']) && isset($sourceMenuById[(int) $row['id_parent']])) {
				$parent = $sourceMenuById[(int) $row['id_parent']];
				$targetParent = $currentMenus[$this->getMenuKey($parent['nama_menu'], $parent['url'])] ?? null;
				$data['id_parent'] = $targetParent['id_menu'] ?? null;
			}

			if (isset($currentMenus[$key])) {
				$this->db->table('core_menu')->where('id_menu', $currentMenus[$key]['id_menu'])->update($data);
				$messages[] = 'Update menu ' . $row['nama_menu'];
			} else {
				$this->db->table('core_menu')->insert($data);
				$currentMenus[$key] = $this->db->table('core_menu')->where('id_menu', $this->db->insertID())->get()->getRowArray();
				$messages[] = 'Insert menu ' . $row['nama_menu'];
			}
		}

		return $messages;
	}

	private function syncCoreMenuRole(): array
	{
		$messages = [];
		$currentMenus = [];
		foreach ($this->db->table('core_menu')->get()->getResultArray() as $row) {
			$currentMenus[$this->getMenuKey($row['nama_menu'], $row['url'])] = (int) $row['id_menu'];
		}

		$currentRoles = [];
		foreach ($this->db->table('core_role')->get()->getResultArray() as $row) {
			$currentRoles[$row['nama_role']] = (int) $row['id_role'];
		}

		$existingPairs = [];
		foreach ($this->db->table('core_menu_role')->get()->getResultArray() as $row) {
			$existingPairs[$row['id_menu'] . ':' . $row['id_role']] = true;
		}

		$sourceMenus = [];
		foreach ($this->parseInsertRows('core_menu') as $row) {
			$sourceMenus[(int) $row['id_menu']] = $this->getMenuKey($row['nama_menu'], $row['url']);
		}

		$roleNames = $this->getRoleNameMap();
		foreach ($this->parseInsertRows('core_menu_role') as $row) {
			$menuKey = $sourceMenus[(int) $row['id_menu']] ?? '';
			$roleName = $roleNames[(int) $row['id_role']] ?? '';
			$idMenu = $currentMenus[$menuKey] ?? 0;
			$idRole = $currentRoles[$roleName] ?? 0;
			if (!$idMenu || !$idRole) {
				continue;
			}

			$key = $idMenu . ':' . $idRole;
			if (!isset($existingPairs[$key])) {
				$this->db->table('core_menu_role')->insert(['id_menu' => $idMenu, 'id_role' => $idRole]);
				$existingPairs[$key] = true;
				$messages[] = 'Insert menu role ' . $menuKey . ' -> ' . $roleName;
			}
		}

		return $messages;
	}

	public function executeSync(): array
	{
		$preview = $this->getPreview();
		$messages = [];
		$errors = [];

		foreach ($preview['schema_changes'] as $change) {
			if ($change['sql'] === '') {
				continue;
			}

			try {
				$this->db->query($change['sql']);
				$messages[] = $change['group'] . ' ' . $change['table'] . ' / ' . $change['name'];
			} catch (\Throwable $e) {
				$errors[] = $change['table'] . ' / ' . $change['name'] . ': ' . $e->getMessage();
			}
		}

		try {
			$messages = array_merge(
				$messages,
				$this->syncCoreModules(),
				$this->syncCoreMenuKategori(),
				$this->syncCoreMenu(),
				$this->syncCoreMenuRole()
			);
		} catch (\Throwable $e) {
			$errors[] = 'Sinkronisasi data core gagal: ' . $e->getMessage();
		}

		if ($errors) {
			return [
				'status' => 'error',
				'message' => $errors,
				'detail' => $messages,
			];
		}

		return [
			'status' => 'ok',
			'message' => $messages ?: ['Tidak ada perubahan yang perlu dijalankan'],
		];
	}
}
