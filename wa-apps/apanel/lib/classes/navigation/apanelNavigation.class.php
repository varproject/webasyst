<?php

final class apanelNavigation
{
    protected static $items = null;
    protected static $is_injected = false;
    protected static $runtime_cache = [];
    protected static $items_hash = null;

    public static function getLvl($level, $default = [], $current = null)
    {
        $level = (int) $level;

        if ($level < 1 || $level > 3) {
            return $default;
        }

        $state = self::buildState($current);
        $key   = 'lvl' . $level;

        return isset($state[$key]) ? $state[$key] : $default;
    }

    public static function getActiveNode($current = null, $key = null, $default = null)
    {
        $state = self::buildState($current);
        $node  = !empty($state['active_node']) && is_array($state['active_node'])
            ? $state['active_node']
            : [];

        if (func_num_args() < 2) {
            return $node ?: $default;
        }

        if (!is_string($key) && !is_int($key)) {
            return $default;
        }

        return isset($node[$key]) ? $node[$key] : $default;
    }

    public static function insertNode($parent_id, $node)
    {
        self::boot();

        if (!$parent_id || !$node) {
            return;
        }

        if (!isset($node['id']) || !isset($node['name'])) {
            foreach ($node as $row) {
                if (is_array($row)) {
                    self::insertNode($parent_id, $row);
                }
            }
            return;
        }

        $parts = explode('.', $parent_id);
        $items = &self::$items;

        foreach ($parts as $part) {
            if (!isset($items[$part])) {
                return;
            }

            if (!isset($items[$part]['children']) || !is_array($items[$part]['children'])) {
                $items[$part]['children'] = [];
            }

            $items = &$items[$part]['children'];
        }

        $id = (string) $node['id'];

        unset($node['parent_id'], $node['level'], $node['is_active']);

        $items[$id] = $node;

        self::resetRuntime();
    }



    
    protected static function buildState($current = null)
    {
        self::boot();
        self::injectNode();

        if ($current === null) {
            $current = apanelUrlSegment::asString();
        }

        if (self::$items_hash === null) {
            self::$items_hash = md5(serialize(self::$items));
        }

        $key = self::$items_hash . '|' . (is_array($current) ? serialize($current) : (string) $current);

        if (isset(self::$runtime_cache[$key])) {
            return self::$runtime_cache[$key];
        }

        $rows = [];
        $url_index = [];
        $tree_ids = [
            'lvl1' => [],
            'lvl2' => [],
            'lvl3' => [],
        ];
        $default_path = [];

        self::walk(self::$items, '', 1, $rows, $tree_ids, $url_index, $default_path);

        $active_path = self::resolveActivePath($current, $url_index, $default_path);
        $active      = $active_path ? array_fill_keys($active_path, 1) : [];

        $result = [
            'lvl1'        => [],
            'lvl2'        => [],
            'lvl3'        => [],
            'active_node' => [],
        ];

        foreach ($tree_ids['lvl1'] as $id) {
            if (isset($rows[$id])) {
                $result['lvl1'][$id] = self::normalizeNode($rows[$id], $active);
            }
        }

        $active_lvl1 = $active_path[0] ?? null;
        if ($active_lvl1 && !empty($tree_ids['lvl2'][$active_lvl1])) {
            foreach ($tree_ids['lvl2'][$active_lvl1] as $id) {
                if (isset($rows[$id])) {
                    $result['lvl2'][$id] = self::normalizeNode($rows[$id], $active);
                }
            }
        }

        $active_lvl2 = $active_path[1] ?? null;
        if ($active_lvl2 && !empty($tree_ids['lvl3'][$active_lvl2])) {
            foreach ($tree_ids['lvl3'][$active_lvl2] as $id) {
                if (isset($rows[$id])) {
                    $result['lvl3'][$id] = self::normalizeNode($rows[$id], $active);
                }
            }
        }

        $active_id = $active_path ? end($active_path) : null;
        if ($active_id && isset($rows[$active_id])) {
            $result['active_node'] = self::normalizeNode($rows[$active_id], $active);
        }

        self::$runtime_cache[$key] = $result;

        return $result;
    }

    protected static function walk($items, $parent_id, $level, &$rows, &$tree_ids, &$url_index, &$default_path)
    {
        if ($level > 3 || !$items) {
            return null;
        }

        $first_node_info = null;

        foreach ($items as $key => $item) {
            if (empty($item['name']) || (isset($item['is_enabled']) && !$item['is_enabled'])) {
                continue;
            }

            $id  = $parent_id ? $parent_id . '.' . $key : (string) $key;
            $url = !empty($item['url']) ? $item['url'] : str_replace('.', '/', $id) . '/';

            $node = [
                'id'           => $id,
                'parent_id'    => $parent_id,
                'level'        => $level,
                'item'         => $item,
                'original_url' => $url,
                'first_id'     => $id,
                'first_url'    => $url,
            ];

            $children = !empty($item['children']) && is_array($item['children']) ? $item['children'] : [];

            if ($children) {
                $child_first = self::walk($children, $id, $level + 1, $rows, $tree_ids, $url_index, $default_path);

                if ($child_first) {
                    $node['first_id']  = $child_first['first_id'];
                    $node['first_url'] = $child_first['first_url'];
                }
            }

            $rows[$id] = $node;

            $path_parts = explode('.', $node['first_id']);
            $path_ids   = [];
            $tmp        = '';

            foreach ($path_parts as $part) {
                $tmp = $tmp ? $tmp . '.' . $part : $part;
                $path_ids[] = $tmp;
            }

            $url_index[$node['original_url']] = $path_ids;
            $url_index[$node['first_url']]    = $path_ids;

            if ($first_node_info === null) {
                $first_node_info = [
                    'first_id'  => $node['first_id'],
                    'first_url' => $node['first_url'],
                ];

                if (!$default_path) {
                    $default_path = $path_ids;
                }
            }
        }

        $sorted = self::sortItems($items);

        foreach ($sorted as $key => $item) {
            $id = $parent_id ? $parent_id . '.' . $key : (string) $key;

            if (!isset($rows[$id])) {
                continue;
            }

            if ($level === 1) {
                $tree_ids['lvl1'][] = $id;
            } else {
                $tree_ids['lvl' . $level][$parent_id][] = $id;
            }
        }

        return $first_node_info;
    }

    protected static function normalizeNode($row, $active = [])
    {
        $item = $row['item'];

        return [
            'id'         => $row['id'],
            'parent_id'  => $row['parent_id'],
            'level'      => $row['level'],
            'name'       => $item['name'],
            'icon'       => !empty($item['icon']) ? apanelGetIcon::html($item['icon']) : '',
            'url'        => $row['first_url'],
            'module'     => !empty($item['module']) ? $item['module'] : apanelStringCase::toCamelCase($row['id']),
            'action'     => $item['action'] ?? '',
            'plugin'     => $item['plugin'] ?? '',
            'sort'       => isset($item['sort']) ? (int) $item['sort'] : 0,
            'is_enabled' => isset($item['is_enabled']) ? (int) $item['is_enabled'] : 1,
            'is_active'  => isset($active[$row['id']]) ? 1 : 0,
        ];
    }

    protected static function sortItems($items)
    {
        if (!$items) {
            return [];
        }

        $prepared = [];
        $index = 0;

        foreach ($items as $key => $item) {
            $prepared[$key] = [
                'item'  => $item,
                'index' => $index++,
            ];
        }

        uasort($prepared, function ($a, $b) {
            $has_a = isset($a['item']['sort']) && $a['item']['sort'] !== '';
            $has_b = isset($b['item']['sort']) && $b['item']['sort'] !== '';

            if (!$has_a && !$has_b) {
                return $a['index'] <=> $b['index'];
            }

            if (!$has_a) {
                return 1;
            }

            if (!$has_b) {
                return -1;
            }

            $result = ((int) $a['item']['sort']) <=> ((int) $b['item']['sort']);

            return $result ?: ($a['index'] <=> $b['index']);
        });

        $result = [];

        foreach ($prepared as $key => $row) {
            $result[$key] = $row['item'];
        }

        return $result;
    }

    protected static function resolveActivePath($current, $index, $default = [])
    {
        if (is_array($current)) {
            $current = $current['original_url'] ?? $current['url'] ?? '';
        }

        if (!is_string($current) || trim($current) === '') {
            return $default;
        }

        $url = trim($current, '/');
        $candidate = $url . '/';

        if (isset($index[$candidate])) {
            return $index[$candidate];
        }

        $parts = explode('/', $url);
        array_pop($parts);

        while ($parts) {
            $candidate = implode('/', $parts) . '/';

            if (isset($index[$candidate])) {
                return $index[$candidate];
            }

            array_pop($parts);
        }

        return $default;
    }

    protected static function injectNode()
    {
        if (self::$is_injected) {
            return;
        }

        $rows = [];

        $params = self::$items;
        $plugin_nodes = wa()->event('navigation_backend', $params) ?: [];

        foreach ($plugin_nodes as $plugin => $items) {
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $row['plugin'] = str_replace('-plugin', '', $plugin);
                $rows[] = $row;
            }
        }

        foreach ($rows as $row) {
            if (empty($row['parent_id']) || empty($row['id']) || empty($row['name'])) {
                continue;
            }

            $parent_id = $row['parent_id'];
            unset($row['parent_id']);

            self::insertNode($parent_id, $row);
        }

        self::$is_injected = true;
    }

    protected static function boot()
    {
        if (self::$items !== null) {
            return;
        }

        self::$items = apanelSettings::get('ui.backend.navigation');
    }

    protected static function resetRuntime()
    {
        self::$items_hash = null;
        self::$runtime_cache = [];
    }
}
