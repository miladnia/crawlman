<?php

/*
 * This file is part of the Chakavang package.
 *
 * (c) Milad Nia <milad@miladnia.ir>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chakavang\Crawlman\Util;

final class XPathUtil
{
    private static function makeCondition(array $data, string $value, string $logic, string $operator): string
    {
        $conditions = [];
        foreach ($data as $d) {
            $conditions[] = $value . $operator . '"' . $d . '"';
        }

        return implode(' ' . $logic . ' ', $conditions);
    }

    public static function makeSearchCondition(array $data, string $value): string
    {
        return self::makeCondition($data, $value, "or", '=');
    }
}
