<?php

namespace Query;

/**
 * Parser - SQL Syntax Parser
 *
 * Parses tokenized SQL into structured arrays for execution
 */
class Parser
{
    private Tokenizer $tokenizer;

    public function __construct()
    {
        $this->tokenizer = new Tokenizer();
    }

    /**
     * Parse a SQL query string
     */
    public function parse(string $sql): array
    {
        $tokens = $this->tokenizer->tokenize($sql);

        if (empty($tokens)) {
            throw new \Exception("Empty SQL query");
        }

        $command = strtoupper($tokens[0]['value'] ?? '');

        switch ($command) {
            case 'SELECT':
                return $this->parseSelect($tokens);
            case 'INSERT':
                return $this->parseInsert($tokens);
            case 'UPDATE':
                return $this->parseUpdate($tokens);
            case 'DELETE':
                return $this->parseDelete($tokens);
            case 'CREATE':
                return $this->parseCreate($tokens);
            case 'DROP':
                return $this->parseDrop($tokens);
            default:
                throw new \Exception("Unknown SQL command: {$command}");
        }
    }

    /**
     * Parse SELECT statement
     */
    private function parseSelect(array $tokens): array
    {
        $result = ['type' => 'SELECT'];
        $i = 1;

        $result['columns'] = $this->parseColumns($tokens, $i);

        if (!Tokenizer::matches($tokens, $i, 'FROM')) {
            throw new \Exception("Expected FROM keyword");
        }
        $i++;

        if (!isset($tokens[$i]) || $tokens[$i]['type'] !== Tokenizer::TOKEN_IDENTIFIER) {
            throw new \Exception("Expected table name after FROM");
        }
        $result['table'] = $tokens[$i]['value'];
        $i++;

        $result['alias'] = $this->parseAlias($tokens, $i);

        if (isset($tokens[$i]) && Tokenizer::matches($tokens, $i, 'INNER')) {
            $result['join'] = $this->parseJoin($tokens, $i);
        }

        if (isset($tokens[$i]) && Tokenizer::matches($tokens, $i, 'WHERE')) {
            $result['where'] = $this->parseWhere($tokens, $i);
        }

        if (isset($tokens[$i]) && Tokenizer::matches($tokens, $i, 'ORDER')) {
            $result['orderBy'] = $this->parseOrderBy($tokens, $i);
        }

        if (isset($tokens[$i]) && Tokenizer::matches($tokens, $i, 'LIMIT')) {
            $i++;
            if ($tokens[$i]['type'] === Tokenizer::TOKEN_NUMBER) {
                $result['limit'] = $tokens[$i]['value'];
                $i++;
            }
        }

        return $result;
    }

    /**
     * Parse INSERT statement
     */
    private function parseInsert(array $tokens): array
    {
        $result = ['type' => 'INSERT'];
        $i = 1;

        if (!Tokenizer::matches($tokens, $i, 'INTO')) {
            throw new \Exception("Expected INTO keyword");
        }
        $i++;

        if ($tokens[$i]['type'] !== Tokenizer::TOKEN_IDENTIFIER) {
            throw new \Exception("Expected table name");
        }
        $result['table'] = $tokens[$i]['value'];
        $i++;

        if ($tokens[$i]['value'] !== '(') {
            throw new \Exception("Expected ( after table name");
        }
        $i++;

        $result['columns'] = [];
        while ($i < count($tokens) && $tokens[$i]['value'] !== ')') {
            if ($tokens[$i]['type'] === Tokenizer::TOKEN_IDENTIFIER) {
                $result['columns'][] = $tokens[$i]['value'];
            }
            $i++;
        }
        $i++;

        if (!Tokenizer::matches($tokens, $i, 'VALUES')) {
            throw new \Exception("Expected VALUES keyword");
        }
        $i++;

        $result['values'] = [];
        while ($i < count($tokens)) {
            if ($tokens[$i]['value'] === '(') {
                $i++;
                $row = [];
                while ($i < count($tokens) && $tokens[$i]['value'] !== ')') {
                    if ($tokens[$i]['type'] === Tokenizer::TOKEN_STRING ||
                        $tokens[$i]['type'] === Tokenizer::TOKEN_NUMBER) {
                        $row[] = $tokens[$i]['value'];
                    } elseif ($tokens[$i]['type'] === Tokenizer::TOKEN_IDENTIFIER) {
                        $value = strtoupper($tokens[$i]['value']);
                        if ($value === 'NULL') {
                            $row[] = null;
                        } else {
                            $row[] = $tokens[$i]['value'];
                        }
                    }
                    $i++;
                }
                $result['values'][] = $row;
                $i++;
            } else {
                $i++;
            }
        }

        return $result;
    }

    /**
     * Parse UPDATE statement
     */
    private function parseUpdate(array $tokens): array
    {
        $result = ['type' => 'UPDATE'];
        $i = 1;

        if ($tokens[$i]['type'] !== Tokenizer::TOKEN_IDENTIFIER) {
            throw new \Exception("Expected table name");
        }
        $result['table'] = $tokens[$i]['value'];
        $i++;

        if (!Tokenizer::matches($tokens, $i, 'SET')) {
            throw new \Exception("Expected SET keyword");
        }
        $i++;

        $result['set'] = [];
        while ($i < count($tokens) && !Tokenizer::matches($tokens, $i, 'WHERE')) {
            if ($tokens[$i]['type'] === Tokenizer::TOKEN_IDENTIFIER) {
                $column = $tokens[$i]['value'];
                $i++;

                if ($tokens[$i]['value'] !== '=') {
                    throw new \Exception("Expected = after column name");
                }
                $i++;

                $value = null;
                if ($tokens[$i]['type'] === Tokenizer::TOKEN_STRING ||
                    $tokens[$i]['type'] === Tokenizer::TOKEN_NUMBER) {
                    $value = $tokens[$i]['value'];
                } elseif ($tokens[$i]['type'] === Tokenizer::TOKEN_IDENTIFIER && strtoupper($tokens[$i]['value']) === 'NULL') {
                    $value = null;
                }

                $result['set'][$column] = $value;
                $i++;
            } else {
                $i++;
            }
        }

        if (isset($tokens[$i]) && Tokenizer::matches($tokens, $i, 'WHERE')) {
            $result['where'] = $this->parseWhere($tokens, $i);
        }

        return $result;
    }

    /**
     * Parse DELETE statement
     */
    private function parseDelete(array $tokens): array
    {
        $result = ['type' => 'DELETE'];
        $i = 1;

        if (!Tokenizer::matches($tokens, $i, 'FROM')) {
            throw new \Exception("Expected FROM keyword");
        }
        $i++;

        if ($tokens[$i]['type'] !== Tokenizer::TOKEN_IDENTIFIER) {
            throw new \Exception("Expected table name");
        }
        $result['table'] = $tokens[$i]['value'];
        $i++;

        if (isset($tokens[$i]) && Tokenizer::matches($tokens, $i, 'WHERE')) {
            $result['where'] = $this->parseWhere($tokens, $i);
        }

        return $result;
    }

    /**
     * Parse CREATE TABLE statement
     */
    private function parseCreate(array $tokens): array
    {
        $result = ['type' => 'CREATE'];
        $i = 1;

        if (!Tokenizer::matches($tokens, $i, 'TABLE')) {
            throw new \Exception("Expected TABLE keyword");
        }
        $i++;

        if ($tokens[$i]['type'] !== Tokenizer::TOKEN_IDENTIFIER) {
            throw new \Exception("Expected table name");
        }
        $result['table'] = $tokens[$i]['value'];
        $i++;

        if ($tokens[$i]['value'] !== '(') {
            throw new \Exception("Expected ( after table name");
        }
        $i++;

        $result['columns'] = [];
        $currentColumn = null;

        while ($i < count($tokens) && $tokens[$i]['value'] !== ')') {
            $token = $tokens[$i];

            if ($token['type'] === Tokenizer::TOKEN_IDENTIFIER && $currentColumn === null) {
                $currentColumn = [
                    'name' => $token['value'],
                    'type' => null,
                    'length' => null,
                    'nullable' => true,
                    'primaryKey' => false,
                    'unique' => false,
                    'autoIncrement' => false,
                    'default' => null
                ];
            } elseif ($token['type'] === Tokenizer::TOKEN_KEYWORD) {
                $keyword = strtoupper($token['value']);

                if (in_array($keyword, ['INT', 'VARCHAR', 'TEXT', 'DATE', 'DATETIME', 'BOOLEAN'])) {
                    $currentColumn['type'] = $keyword;

                    if ($keyword === 'VARCHAR' && isset($tokens[$i + 1]) && $tokens[$i + 1]['value'] === '(') {
                        $i += 2;
                        if ($tokens[$i]['type'] === Tokenizer::TOKEN_NUMBER) {
                            $currentColumn['length'] = $tokens[$i]['value'];
                        }
                        $i++;
                    }
                } elseif ($keyword === 'PRIMARY') {
                    if (isset($tokens[$i + 1]) && Tokenizer::matches($tokens, $i + 1, 'KEY')) {
                        $currentColumn['primaryKey'] = true;
                        $currentColumn['nullable'] = false;
                        $currentColumn['unique'] = true;
                        $i++;
                    }
                } elseif ($keyword === 'UNIQUE') {
                    $currentColumn['unique'] = true;
                } elseif ($keyword === 'NOT') {
                    if (isset($tokens[$i + 1]) && Tokenizer::matches($tokens, $i + 1, 'NULL')) {
                        $currentColumn['nullable'] = false;
                        $i++;
                    }
                } elseif ($keyword === 'AUTO_INCREMENT') {
                    $currentColumn['autoIncrement'] = true;
                } elseif ($keyword === 'DEFAULT') {
                    $i++;
                    if (isset($tokens[$i])) {
                        if ($tokens[$i]['type'] === Tokenizer::TOKEN_STRING ||
                            $tokens[$i]['type'] === Tokenizer::TOKEN_NUMBER) {
                            $currentColumn['default'] = $tokens[$i]['value'];
                        } elseif (Tokenizer::matches($tokens, $i, 'NULL')) {
                            $currentColumn['default'] = null;
                        }
                    }
                }
            } elseif ($token['value'] === ',') {
                if ($currentColumn !== null) {
                    $result['columns'][$currentColumn['name']] = $currentColumn;
                    $currentColumn = null;
                }
            }

            $i++;
        }

        if ($currentColumn !== null) {
            $result['columns'][$currentColumn['name']] = $currentColumn;
        }

        return $result;
    }

    /**
     * Parse DROP TABLE statement
     */
    private function parseDrop(array $tokens): array
    {
        $result = ['type' => 'DROP'];
        $i = 1;

        if (!Tokenizer::matches($tokens, $i, 'TABLE')) {
            throw new \Exception("Expected TABLE keyword");
        }
        $i++;

        if ($tokens[$i]['type'] !== Tokenizer::TOKEN_IDENTIFIER) {
            throw new \Exception("Expected table name");
        }
        $result['table'] = $tokens[$i]['value'];

        return $result;
    }

    /**
     * Parse column list for SELECT
     */
    private function parseColumns(array $tokens, int &$i): array
    {
        $columns = [];

        while ($i < count($tokens) && !Tokenizer::matches($tokens, $i, 'FROM')) {
            $token = $tokens[$i];

            if ($token['value'] === '*') {
                $columns[] = '*';
                $i++;
            } elseif ($token['type'] === Tokenizer::TOKEN_IDENTIFIER) {
                $column = $token['value'];

                if (isset($tokens[$i + 1]) && $tokens[$i + 1]['value'] === '.') {
                    $table = $column;
                    $i += 2;
                    if ($tokens[$i]['type'] === Tokenizer::TOKEN_IDENTIFIER || $tokens[$i]['value'] === '*') {
                        $column = $table . '.' . $tokens[$i]['value'];
                    }
                }

                $columns[] = $column;
                $i++;
            } else {
                $i++;
            }
        }

        return $columns;
    }

    /**
     * Parse table alias
     */
    private function parseAlias(array $tokens, int &$i): ?string
    {
        if (isset($tokens[$i]) &&
            ($tokens[$i]['type'] === Tokenizer::TOKEN_IDENTIFIER && !Tokenizer::matches($tokens, $i, 'WHERE') && !Tokenizer::matches($tokens, $i, 'INNER') && !Tokenizer::matches($tokens, $i, 'ORDER') && !Tokenizer::matches($tokens, $i, 'LIMIT'))) {
            if (Tokenizer::matches($tokens, $i, 'AS')) {
                $i++;
            }
            if (isset($tokens[$i]) && $tokens[$i]['type'] === Tokenizer::TOKEN_IDENTIFIER) {
                $alias = $tokens[$i]['value'];
                $i++;
                return $alias;
            }
        }
        return null;
    }

    /**
     * Parse WHERE clause
     */
    private function parseWhere(array $tokens, int &$i): array
    {
        $i++;
        $conditions = [];
        $currentCondition = [];

        while ($i < count($tokens) &&
               !Tokenizer::matches($tokens, $i, 'ORDER') &&
               !Tokenizer::matches($tokens, $i, 'LIMIT') &&
               !Tokenizer::matches($tokens, $i, 'GROUP')) {
            $token = $tokens[$i];

            if ($token['type'] === Tokenizer::TOKEN_IDENTIFIER) {
                if (empty($currentCondition)) {
                    $currentCondition['column'] = $token['value'];
                }
            } elseif ($token['type'] === Tokenizer::TOKEN_OPERATOR) {
                $currentCondition['operator'] = $token['value'];
            } elseif ($token['type'] === Tokenizer::TOKEN_STRING || $token['type'] === Tokenizer::TOKEN_NUMBER) {
                $currentCondition['value'] = $token['value'];
            } elseif ($token['type'] === Tokenizer::TOKEN_KEYWORD) {
                $keyword = strtoupper($token['value']);
                if ($keyword === 'NULL') {
                    $currentCondition['value'] = null;
                } elseif ($keyword === 'LIKE' || $keyword === 'IN') {
                    $currentCondition['operator'] = $keyword;
                } elseif ($keyword === 'IS') {
                    $currentCondition['operator'] = 'IS';
                } elseif ($keyword === 'AND' || $keyword === 'OR') {
                    $currentCondition['logic'] = $keyword;
                    $conditions[] = $currentCondition;
                    $currentCondition = [];
                }
            }

            $i++;
        }

        if (!empty($currentCondition)) {
            $currentCondition['logic'] = null;
            $conditions[] = $currentCondition;
        }

        return ['conditions' => $conditions];
    }

    /**
     * Parse JOIN clause
     */
    private function parseJoin(array $tokens, int &$i): array
    {
        $join = ['type' => 'INNER'];

        if (Tokenizer::matches($tokens, $i, 'INNER')) {
            $i++;
        }

        if (!Tokenizer::matches($tokens, $i, 'JOIN')) {
            throw new \Exception("Expected JOIN keyword");
        }
        $i++;

        if ($tokens[$i]['type'] !== Tokenizer::TOKEN_IDENTIFIER) {
            throw new \Exception("Expected table name after JOIN");
        }
        $join['table'] = $tokens[$i]['value'];
        $i++;

        $join['alias'] = $this->parseAlias($tokens, $i);

        if (!Tokenizer::matches($tokens, $i, 'ON')) {
            throw new \Exception("Expected ON keyword");
        }
        $i++;

        $leftPart = $tokens[$i]['value'];
        $i++;
        if ($tokens[$i]['value'] === '.') {
            $i++;
            $leftPart .= '.' . $tokens[$i]['value'];
            $i++;
        }

        if ($tokens[$i]['value'] !== '=') {
            throw new \Exception("Expected = in JOIN condition");
        }
        $i++;

        $rightPart = $tokens[$i]['value'];
        $i++;
        if (isset($tokens[$i]) && $tokens[$i]['value'] === '.') {
            $i++;
            $rightPart .= '.' . $tokens[$i]['value'];
            $i++;
        }

        $leftParts = explode('.', $leftPart);
        $rightParts = explode('.', $rightPart);

        $join['on'] = [
            'left' => [
                'table' => $leftParts[0] ?? null,
                'column' => $leftParts[1] ?? $leftParts[0]
            ],
            'right' => [
                'table' => $rightParts[0] ?? null,
                'column' => $rightParts[1] ?? $rightParts[0]
            ]
        ];

        return $join;
    }

    /**
     * Parse ORDER BY clause
     */
    private function parseOrderBy(array $tokens, int &$i): array
    {
        $i++;

        if (!Tokenizer::matches($tokens, $i, 'BY')) {
            throw new \Exception("Expected BY after ORDER");
        }
        $i++;

        $orderBy = [];

        while ($i < count($tokens) && !Tokenizer::matches($tokens, $i, 'LIMIT')) {
            if ($tokens[$i]['type'] === Tokenizer::TOKEN_IDENTIFIER) {
                $order = [
                    'column' => $tokens[$i]['value'],
                    'direction' => 'ASC'
                ];
                $i++;

                if (isset($tokens[$i]) && (Tokenizer::matches($tokens, $i, 'ASC') || Tokenizer::matches($tokens, $i, 'DESC'))) {
                    $order['direction'] = strtoupper($tokens[$i]['value']);
                    $i++;
                }

                $orderBy[] = $order;
            } else {
                $i++;
            }
        }

        return $orderBy;
    }
}