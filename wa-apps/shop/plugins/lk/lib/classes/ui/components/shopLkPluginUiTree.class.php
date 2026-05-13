<?php

/**
 * shopLkPluginUiTree
 *
 * Назначение:
 * - подготавливать состояние tree-компонента;
 * - хранить список открытых узлов в сессии;
 * - строить дерево из плоского массива узлов.
 *
 * Зависимости:
 * - waRequest;
 * - waSystem storage.
 *
 * Инварианты:
 * - open_ids хранятся в сессии по session_open_ids_key;
 * - active_id сохраняется в сессии по session_active_id_key;
 * - дерево строится только по открытым веткам;
 * - если у закрытого узла есть потомки, childs = true;
 * - если узел открыт, childs содержит массив дочерних узлов;
 * - если у узла нет потомков, ключ childs отсутствует.
 *
 * Побочные эффекты:
 * - читает и записывает данные в сессию;
 * - обрабатывает query-параметры reset и toggle.
 *
 * Ошибки:
 * - при пустом наборе nodes возвращается пустое дерево.
 */
final class shopLkPluginUiTree
{
    /**
     * Подготавливает параметры tree-компонента.
     * Обязательно нужно сделать return $params
     *
     * @param array $params
     * @return array
     */
    public static function execute(array $params = []): array
    {
        $session = wa()->getStorage();

        $session_open_ids_key   = $params['session_open_ids_key'] ?? 'lk_node_ids';
        $session_active_id_key  = $params['session_active_id_key'] ?? 'lk_active_node_id';

        if (waRequest::get('reset')) {
            $session->remove($session_active_id_key);
            $session->remove($session_open_ids_key);

            $active_node_id = 0;
            $open_ids       = [];

            shopLkPluginRedirect::redirectBack(true);
        } else {
            $active_node_id = (int) ($params['active_id'] ?? 0);
            $open_ids       = $session->read($session_open_ids_key) ?: [];
        }

        $session->write($session_active_id_key, $active_node_id);

        $toggle_id = waRequest::get('toggle', null, waRequest::TYPE_INT);

        if ($toggle_id && self::hasChildren($params['nodes'] ?? [], $toggle_id)) {
            $key = array_search($toggle_id, $open_ids, true);

            if ($key !== false) {
                unset($open_ids[$key]);
            } else {
                $open_ids[] = $toggle_id;
            }

            $open_ids = array_values(array_unique($open_ids));
            $session->write($session_open_ids_key, $open_ids);
        }

        $params['open_ids'] = $open_ids;
        $params['nodes']    = self::buildTree($params['nodes'] ?? [], $open_ids);

        return $params;
    }


    /**
     * Сбрасывает состояние дерева.
     *
     * @param string $session_open_ids_key
     * @param string $session_active_id_key
     * @return void
     */
    public static function resetState($session_open_ids_key, $session_active_id_key)
    {
        $session = wa()->getStorage();

        $session->remove($session_active_id_key);
        $session->remove($session_open_ids_key);
    }

    /**
     * Разворачивает все узлы, у которых есть потомки.
     *
     * @param array $nodes
     * @param string $session_open_ids_key
     * @return void
     */
    public static function expandAll(array $nodes, $session_open_ids_key)
    {
        $session  = wa()->getStorage();
        $open_ids = [];

        foreach ($nodes as $node_id => $node) {
            $node_id = (int) $node_id;

            if (self::hasChildren($nodes, $node_id)) {
                $open_ids[] = $node_id;
            }
        }

        $session->write($session_open_ids_key, array_values(array_unique($open_ids)));
    }

    /**
     * Сворачивает всё, кроме активной ветки.
     *
     * Логика:
     * - родители активного узла всегда остаются открытыми;
     * - сам активный узел остаётся открытым только если:
     *   1) у него есть потомки;
     *   2) он уже был раскрыт до сворачивания.
     *
     * @param array $nodes
     * @param int $active_id
     * @param string $session_open_ids_key
     * @return void
     */
    public static function collapseAllExceptActive(array $nodes, $active_id, $session_open_ids_key)
    {
        $session          = wa()->getStorage();
        $current_open_ids = $session->read($session_open_ids_key) ?: [];
        $open_ids         = self::getActiveBranchIds($nodes, $active_id, $current_open_ids);

        $session->write($session_open_ids_key, $open_ids);
    }












    /**
     * Строит дерево из плоского массива.
     *
     * Важно:
     * - сборка идёт по ссылкам;
     * - ключ childs не создаётся у листьев;
     * - у закрытого узла с потомками ставится childs = true.
     *
     * @param array $data
     * @param array $open_ids
     * @return array
     */
    protected static function buildTree(array $data, array $open_ids = []): array
    {
        $tree = [];

        foreach ($data as $id => &$node) {
            $parent_id = (int) ($node['parent_id'] ?? 0);

            if ($parent_id === 0) {
                $tree[$id] = &$node;
            } else {
                if (in_array($parent_id, $open_ids, true)) {
                    if (isset($data[$parent_id])) {
                        $data[$parent_id]['childs'][$id] = &$node;
                    }
                } else {
                    if (isset($data[$parent_id])) {
                        $data[$parent_id]['childs'] = true;
                    }
                }
            }
        }

        unset($node);

        return $tree;
    }

    /**
     * Возвращает список узлов, которые должны остаться открытыми.
     *
     * @param array $nodes
     * @param int $active_id
     * @param array $current_open_ids
     * @return array
     */
    protected static function getActiveBranchIds(array $nodes, $active_id, array $current_open_ids = [])
    {
        $active_id = (int) $active_id;

        if ($active_id <= 0 || empty($nodes[$active_id])) {
            return [];
        }

        $open_ids = [];

        // Сам активный узел оставляем открытым только если он уже был открыт
        // и у него есть потомки
        if (
            self::hasChildren($nodes, $active_id)
            && in_array($active_id, $current_open_ids, true)
        ) {
            $open_ids[] = $active_id;
        }

        // Всех родителей активного узла оставляем открытыми
        $parent_id = (int) ($nodes[$active_id]['parent_id'] ?? 0);

        while ($parent_id > 0 && !empty($nodes[$parent_id])) {
            if (self::hasChildren($nodes, $parent_id)) {
                $open_ids[] = $parent_id;
            }

            $parent_id = (int) ($nodes[$parent_id]['parent_id'] ?? 0);
        }

        return array_values(array_unique($open_ids));
    }

    /**
     * Проверяет, есть ли у узла потомки.
     *
     * @param array $nodes
     * @param int $node_id
     * @return bool
     */
    protected static function hasChildren(array $nodes, $node_id)
    {
        $node_id = (int) $node_id;

        foreach ($nodes as $node) {
            if ((int) ($node['parent_id'] ?? 0) === $node_id) {
                return true;
            }
        }

        return false;
    }
}
