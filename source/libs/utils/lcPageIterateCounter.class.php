<?php
declare(strict_types=1);

/*
 * Lightcast - A PHP MVC Framework
* Copyright (C) 2005 Nimasystems Ltd
*
* This program is NOT free software; you cannot redistribute and/or modify
* it's sources under any circumstances without the explicit knowledge and
* agreement of the rightful owner of the software - Nimasystems Ltd.
*
* This program is distributed WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
* PURPOSE.  See the LICENSE.txt file for more information.
*
* You should have received a copy of LICENSE.txt file along with this
* program; if not, write to:
* NIMASYSTEMS LTD
* Plovdiv, Bulgaria
* ZIP Code: 4000
* Address: 95 "Kapitan Raycho" Str.
* E-Mail: info@nimasystems.com


*/

/**
 *
 */
class lcPageIterateCounter
{
    public const DEFAULT_GROUP_LIMIT = 10;

    public static function getStats(int $numHits, int $limit, int $page, int $group_limiter = self::DEFAULT_GROUP_LIMIT): array
    {
        if (!$limit || !$page || !$group_limiter) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        $default = [
            'rows_from' => 0,
            'rows_to' => 0,
            'total_rows' => 0,
            'total_pages' => 0,
            'previous_page' => 0,
            'next_page' => 0,
        ];

        if (!$numHits) {
            return $default;
        }

        $total = $numHits;
        $limit = max($limit, 1);
        $num_pages = ceil($numHits / $limit);
        $page = min(max($page, 1), $num_pages);
        $offset = ($page - 1) * $limit;

        $count = 0;

        if ($group_limiter > 0) {
            $cur_page = $page;
            $cur_page = max($cur_page - floor($group_limiter / 2), 1);
            $cur_page = min($cur_page, $num_pages - $group_limiter + 1);
            $cur_page = max($cur_page, 1);
        } else {
            $cur_page = 1;
        }

        $result = [];

        for ($i = $cur_page; $i <= $num_pages; $i++) {

            if ($group_limiter) {
                if ($count >= $group_limiter) {
                    break;
                }

                ++$count;
            }

            if ($i == $page) {
                $result['selected_page'] = $i;
            }

            $result['active_pages'][] = $i;
        }

        $result['rows_from'] = $offset + 1;
        $result['rows_to'] = $offset + $limit;
        $result['total_rows'] = $total;
        $result['total_pages'] = $num_pages;
        $result['previous_page'] = ($page != 1) ? $page - 1 : null;
        $result['next_page'] = ($page != $num_pages) ? $page + 1 : null;

        return $result;
    }
}
