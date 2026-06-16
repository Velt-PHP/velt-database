<?php

declare(strict_types=1);

namespace Velt\Database\Query;

use InvalidArgumentException;

final class SqlIdentifier
{
    public static function quote(string $identifier): string
    {
        if ($identifier === '*') {
            return '*';
        }

        $parts = explode('.', $identifier);

        foreach ($parts as $part) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $part)) {
                throw new InvalidArgumentException(sprintf('Invalid SQL identifier "%s".', $identifier));
            }
        }

        return implode('.', array_map(
            static fn (string $part): string => '"' . $part . '"',
            $parts,
        ));
    }
}
