<?php

namespace TickTackk\RouteOnSubdomain\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure as EntityStructure;

/**
 * Class RouteOnSubdomain
 *
 * @package TickTackk\RouteOnSubdomain\XF\Entity
 *
 * COLUMNS
 * @property string route_prefix
 * @property bool   is_on_subdomain
 */
class RouteOnSubdomain extends Entity
{
    /**
     * Setup entity structure
     *
     * @param EntityStructure $structure
     *
     * @return EntityStructure
     */
    public static function getStructure(EntityStructure $structure) : EntityStructure
    {
        $structure->shortName = 'TickTackk\RouteOnSubdomain:RouteOnSubdomain';
        $structure->table = 'xf_tck_route_on_subdomain';
        $structure->primaryKey = 'route_prefix';
        $structure->columns = [
            'route_prefix' => ['type' => static::STR, 'required' => true, 'unique' => true],
            'is_on_subdomain' => ['type' => static::BOOL, 'default' => false]
        ];

        return $structure;
    }
}