<?php

namespace Query;

/**
 * Tokenizer - SQL Lexical Analyzer
 *
 * Breaks SQL strings into tokens for parsing
 */
class Tokenizer
{
    const TOKEN_KEYWORD = 'KEYWORD';
    const TOKEN_IDENTIFIER = 'IDENTIFIER';
    const TOKEN_STRING = 'STRING';
    const TOKEN_NUMBER = 'NUMBER';
    const TOKEN_OPERATOR = 'OPERATOR';
    const TOKEN_SYMBOL = 'SYMBOL';

    private array $keywords = [
        'SELECT', 'FROM', 'WHERE', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'SET',
        'DELETE', 'CREATE', 'TABLE', 'DROP', 'PRIMARY', 'KEY', 'UNIQUE',
        'NOT', 'NULL', 'DEFAULT', 'AUTO_INCREMENT', 'INT', 'VARCHAR', 'TEXT',
        'DATE', 'DATETIME', 'BOOLEAN', 'AND', 'OR', 'LIKE', 'IN', 'IS',
        'ORDER', 'BY', 'ASC', 'DESC', 'LIMIT', 'JOIN', 'INNER', 'LEFT', 'RIGHT',
        'ON', 'AS'
    ];

    /**
     * Tokenize a SQL string
     */
    public function tokenize(string $sql): array
    {
        $tokens = [];
        $length = strlen($sql);
        $i = 0;

        while ($i < $length) {
            $char = $sql[$i];

            if (ctype_space($char)) {
                $i++;
                continue;
            }

            if ($char === "'" || $char === '"') {
                $token = $this->extractString($sql, $i, $char);
                $tokens[] = $token;
                $i = $token['end'];
                continue;
            }

            if (ctype_digit($char) || ($char === '-' && $i + 1 < $length && ctype_digit($sql[$i + 1]))) {
                $token = $this->extractNumber($sql, $i);
                $tokens[] = $token;
                $i = $token['end'];
                continue;
            }

            if (in_array($char, ['(', ')', ',', '*', '.', ';'])) {
                $tokens[] = [
                    'type' => self::TOKEN_SYMBOL,
                    'value' => $char,
                    'position' => $i
                ];
                $i++;
                continue;
            }

            if (in_array($char, ['=', '!', '<', '>'])) {
                $token = $this->extractOperator($sql, $i);
                $tokens[] = $token;
                $i = $token['end'];
                continue;
            }

            if (ctype_alpha($char) || $char === '_' || $char === '`') {
                $token = $this->extractIdentifierOrKeyword($sql, $i);
                $tokens[] = $token;
                $i = $token['end'];
                continue;
            }

            $i++;
        }

        return $tokens;
    }

    /**
     * Extract a quoted string
     */
    private function extractString(string $sql, int $start, string $quote): array
    {
        $i = $start + 1;
        $length = strlen($sql);
        $value = '';

        while ($i < $length) {
            $char = $sql[$i];

            if ($char === $quote) {
                if ($i + 1 < $length && $sql[$i + 1] === $quote) {
                    $value .= $quote;
                    $i += 2;
                } else {
                    return [
                        'type' => self::TOKEN_STRING,
                        'value' => $value,
                        'position' => $start,
                        'end' => $i + 1
                    ];
                }
            } else if ($char === '\\' && $i + 1 < $length) {
                $nextChar = $sql[$i + 1];
                if ($nextChar === 'n') {
                    $value .= "\n";
                } elseif ($nextChar === 't') {
                    $value .= "\t";
                } elseif ($nextChar === 'r') {
                    $value .= "\r";
                } else {
                    $value .= $nextChar;
                }
                $i += 2;
            } else {
                $value .= $char;
                $i++;
            }
        }

        throw new \Exception("Unterminated string starting at position {$start}");
    }

    /**
     * Extract a number
     */
    private function extractNumber(string $sql, int $start): array
    {
        $i = $start;
        $length = strlen($sql);
        $value = '';

        if ($sql[$i] === '-') {
            $value .= '-';
            $i++;
        }

        while ($i < $length && (ctype_digit($sql[$i]) || $sql[$i] === '.')) {
            $value .= $sql[$i];
            $i++;
        }

        return [
            'type' => self::TOKEN_NUMBER,
            'value' => strpos($value, '.') !== false ? (float) $value : (int) $value,
            'position' => $start,
            'end' => $i
        ];
    }

    /**
     * Extract an operator
     */
    private function extractOperator(string $sql, int $start): array
    {
        $char = $sql[$start];
        $next = isset($sql[$start + 1]) ? $sql[$start + 1] : '';

        if (($char === '!' && $next === '=') || ($char === '<' && $next === '=') || ($char === '>' && $next === '=') || ($char === '<' && $next === '>')) {
            return [
                'type' => self::TOKEN_OPERATOR,
                'value' => $char . $next,
                'position' => $start,
                'end' => $start + 2
            ];
        }

        return [
            'type' => self::TOKEN_OPERATOR,
            'value' => $char,
            'position' => $start,
            'end' => $start + 1
        ];
    }

    /**
     * Extract an identifier or keyword
     */
    private function extractIdentifierOrKeyword(string $sql, int $start): array
    {
        $i = $start;
        $length = strlen($sql);
        $value = '';
        $isBacktick = $sql[$i] === '`';

        if ($isBacktick) {
            $i++;
            while ($i < $length && $sql[$i] !== '`') {
                $value .= $sql[$i];
                $i++;
            }
            if ($i < $length) {
                $i++;
            }

            return [
                'type' => self::TOKEN_IDENTIFIER,
                'value' => $value,
                'position' => $start,
                'end' => $i
            ];
        }

        while ($i < $length && (ctype_alnum($sql[$i]) || $sql[$i] === '_')) {
            $value .= $sql[$i];
            $i++;
        }

        $upperValue = strtoupper($value);

        if (in_array($upperValue, $this->keywords)) {
            return [
                'type' => self::TOKEN_KEYWORD,
                'value' => $upperValue,
                'position' => $start,
                'end' => $i
            ];
        }

        return [
            'type' => self::TOKEN_IDENTIFIER,
            'value' => $value,
            'position' => $start,
            'end' => $i
        ];
    }

    /**
     * Get token value at index
     */
    public static function getValue(array $tokens, int $index): ?string
    {
        return $tokens[$index]['value'] ?? null;
    }

    /**
     * Get token type at index
     */
    public static function getType(array $tokens, int $index): ?string
    {
        return $tokens[$index]['type'] ?? null;
    }

    /**
     * Check if token at index matches value
     */
    public static function matches(array $tokens, int $index, string $value): bool
    {
        return isset($tokens[$index]) && strtoupper($tokens[$index]['value']) === strtoupper($value);
    }
}
