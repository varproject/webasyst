<?php

class apanelCatalogModel extends waModel
{
    protected $table = 'apanel_catalog';

    /**
     * Возвращает все каталоги по сортировке.
     * use waRuntimeCache
     *
     * @return array
     */
    public function getAllCatalogs()
    {
        $cache = new waRuntimeCache('apanel_catalog/getAllCatalogs');

        $data = $cache->get();
        if ($data !== null) {
            return $data;
        }

        $data = $this->order('sort ASC')->fetchAll('id');

        $cache->set($data);

        return $data;
    }

    /**
     * Возвращает полный список каталогов, список активных и текущий выбранный каталог.
     * * @return array{all: array, enabled: array, default: array|null}
     */
    public function getCatalogsData($current_id = null): array
    {
        if ($current_id === null) {
            $current_id = apanelUrlSegment::getInt(3);
        }

        $all_catalogs = $this->getAllCatalogs();

        // Собираем включенные
        $enabled_catalogs = [];
        foreach ($all_catalogs as $id => $catalog) {
            if (!empty($catalog['is_enabled'])) {
                $enabled_catalogs[$id] = $catalog;
            }
        }

        // Определяем дефолтный каталог (текущий из URL или первый активный)
        $default_catalog = $enabled_catalogs[$current_id] ?? (reset($enabled_catalogs) ?: null);

        return [
            'all'     => $all_catalogs,
            'enabled' => $enabled_catalogs,
            'default' => $default_catalog,
        ];
    }

    /**
     * Возвращает только включённые каталоги по сортировке.
     * use waRuntimeCache
     *
     * @return array
     */
    public function getEnabledCatalogs()
    {
        $cache = new waRuntimeCache('apanel_catalog/getEnabledCatalogs');

        $data = $cache->get();
        if ($data !== null) {
            return $data;
        }

        $data = $this->where('is_enabled = ?', 1)
            ->order('sort ASC')
            ->fetchAll('id');

        $cache->set($data);

        return $data;
    }

    /**
     * Возвращает id первого включённого каталога по сортировке.
     * use waRuntimeCache
     *
     * @return int
     */
    public function getFirstEnabledCatalogId()
    {
        $cache = new waRuntimeCache('apanel_catalog/getFirstEnabledCatalogId');

        $data = $cache->get();
        if ($data !== null) {
            return $data;
        }

        $data = (int) $this->select('id')
            ->where('is_enabled = ?', 1)
            ->order('sort ASC')
            ->fetchField();

        $cache->set($data);

        return $data;
    }

    /**
     * Возвращает включённый каталог по переданному id.
     * Если такой каталог не найден или он выключен — возвращает первый включённый.
     * use $this->getEnabledCatalogs();
     *
     * @param int $catalog_id
     * @return array
     */
    public function getDefaultCatalogById($catalog_id)
    {
        $catalog_id = (int) $catalog_id;
        $catalogs   = $this->getEnabledCatalogs();

        if (isset($catalogs[$catalog_id])) {
            return $catalogs[$catalog_id];
        }

        return $catalogs ? reset($catalogs) : [];
    }

    /**
     * Возвращает каталог по id.
     * Можно получить либо любой каталог, либо только включённый.
     * use $this->getEnabledCatalogs();
     * use $this->getAllCatalogs();
     *
     * @param int $catalog_id
     * @param bool $only_enabled
     * @return array
     */
    public function getCatalogById($catalog_id, $only_enabled = false)
    {
        $catalog_id = (int) $catalog_id;

        if ($catalog_id <= 0) {
            return [];
        }

        $catalogs = $only_enabled
            ? $this->getEnabledCatalogs()
            : $this->getAllCatalogs();

        return isset($catalogs[$catalog_id]) ? $catalogs[$catalog_id] : [];
    }

    /**
     * Создаёт каталог.
     *
     * @param array $data
     * @return int
     * @throws waException
     * @throws Exception
     */
    public function add(array $data)
    {
        $name = $this->normalizeName(isset($data['name']) ? $data['name'] : '');

        $description = isset($data['description'])
            ? $this->normalizeDescription($data['description'])
            : '';

        $icon = isset($data['icon'])
            ? $this->normalizeIcon($data['icon'])
            : '';

        $sort = isset($data['sort'])
            ? $this->normalizeInt($data['sort'])
            : $this->getNextSort();

        $is_enabled = isset($data['is_enabled'])
            ? $this->normalizeBoolInt($data['is_enabled'])
            : 1;

        $now        = date('Y-m-d H:i:s');
        $contact_id = $this->getContactId();

        $catalog_id = (int) $this->insert([
            'name'               => $name,
            'description'        => $description,
            'icon'               => $icon,
            'sort'               => $sort,
            'is_enabled'         => $is_enabled,
            'created_contact_id' => $contact_id,
            'updated_contact_id' => $contact_id,
            'created_datetime'   => $now,
            'updated_datetime'   => $now,
        ]);

        if ($catalog_id <= 0) {
            throw new waException('Не удалось создать каталог.');
        }

        $this->clearRuntimeCache();

        return $catalog_id;
    }

    /**
     * Обновляет каталог.
     *
     * @param int $catalog_id
     * @param array $data
     * @return int
     * @throws waException
     * @throws Exception
     */
    public function update($catalog_id, array $data)
    {
        $catalog = $this->getById($catalog_id);
        if (!$catalog) {
            throw new waException('Каталог не найден.');
        }

        if (!empty($catalog['is_system'])) {
            if (array_key_exists('is_enabled', $data) && !$this->normalizeBoolInt($data['is_enabled'])) {
                throw new waException('Системный каталог нельзя отключить.');
            }
        }

        $update = [];

        if (array_key_exists('name', $data)) {
            $update['name'] = $this->normalizeName($data['name']);
        }

        if (array_key_exists('description', $data)) {
            $update['description'] = $this->normalizeDescription($data['description']);
        }

        if (array_key_exists('icon', $data)) {
            $update['icon'] = $this->normalizeIcon($data['icon']);
        }

        if (array_key_exists('sort', $data)) {
            $update['sort'] = $this->normalizeInt($data['sort']);
        }

        if (array_key_exists('is_enabled', $data)) {
            $update['is_enabled'] = $this->normalizeBoolInt($data['is_enabled']);
        }

        if (!$update) {
            return;
        }

        $update['updated_contact_id'] = $this->getContactId();
        $update['updated_datetime']   = date('Y-m-d H:i:s');

        $this->updateById($catalog_id, $update);
        $this->clearRuntimeCache();

        return $catalog_id;
    }

    /**
     * Переключает доступность каталога.
     *
     * @param int $catalog_id
     * @param bool|int $is_enabled
     * @throws waException
     * @throws Exception
     */
    public function setEnabled($catalog_id, $is_enabled)
    {
        $this->update($catalog_id, [
            'is_enabled' => $is_enabled,
        ]);
    }

    /**
     * Удаляет каталог.
     *
     * @param int $catalog_id
     * @throws waException
     * @throws Exception
     */
    public function delete($catalog_id)
    {
        $catalog = $this->getById($catalog_id);
        if (!$catalog) {
            throw new waException('Каталог не найден.');
        }

        if (!empty($catalog['is_system'])) {
            throw new waException('Системный каталог нельзя удалить.');
        }

        $this->exec('START TRANSACTION');

        try {
            (new apanelProductCategoriesModel())->deleteByField('catalog_id', $catalog_id);
            (new apanelCatalogProductModel())->deleteByField('catalog_id', $catalog_id);
            (new apanelCategoryModel())->deleteByField('catalog_id', $catalog_id);

            $this->deleteById($catalog_id);

            $this->exec('COMMIT');
            $this->clearRuntimeCache();
        } catch (Exception $e) {
            $this->exec('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Сохраняет сортировку каталогов.
     *
     * @param array $ids
     * @throws Exception
     */
    public function saveSort($ids = [])
    {
        $ids = array_map('intval', (array) $ids);
        $ids = array_values(array_unique(array_filter($ids)));

        if (!$ids) {
            return;
        }

        $sort = 10;
        $update = [
            'updated_contact_id' => $this->getContactId(),
            'updated_datetime'   => date('Y-m-d H:i:s'),
        ];

        $this->exec('START TRANSACTION');

        try {
            foreach ($ids as $catalog_id) {
                $update['sort'] = $sort;

                $this->updateById($catalog_id, $update);

                $sort += 10;
            }

            $this->exec('COMMIT');
            $this->clearRuntimeCache();
        } catch (Exception $e) {
            $this->exec('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Очищает runtime-кэш каталога.
     */
    public function clearRuntimeCache()
    {
        (new waRuntimeCache('apanel_catalog/getAllCatalogs'))->delete();
        (new waRuntimeCache('apanel_catalog/getEnabledCatalogs'))->delete();
        (new waRuntimeCache('apanel_catalog/getFirstEnabledCatalogId'))->delete();
    }

    /**
     * Нормализует название каталога.
     *
     * @param mixed $value
     * @return string
     * @throws waException
     */
    private function normalizeName($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new waException('Не указано название каталога.');
        }

        return $this->sanitize($value);
    }

    /**
     * Нормализует описание каталога.
     *
     * @param mixed $value
     * @return string
     */
    private function normalizeDescription($value)
    {
        return $this->sanitize(trim((string) $value));
    }

    /**
     * Нормализует иконку каталога.
     *
     * @param mixed $value
     * @return string
     */
    private function normalizeIcon($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (
            stripos($value, '<i class=') !== false
            && stripos($value, '</i>') !== false
        ) {
            return apanelGetIcon::font($value);
        }

        return '';
    }

    /**
     * Нормализует целое число.
     *
     * @param mixed $value
     * @return int
     */
    private function normalizeInt($value)
    {
        return (int) $value;
    }

    /**
     * Нормализует bool в 0 или 1.
     *
     * @param mixed $value
     * @return int
     */
    private function normalizeBoolInt($value)
    {
        return empty($value) ? 0 : 1;
    }

    /**
     * Очищает строку через sanitizer.
     *
     * @param mixed $value
     * @return string
     */
    private function sanitize($value)
    {
        return (new waHtmlSanitizer())->sanitize((string) $value);
    }

    /**
     * Возвращает следующий sort.
     *
     * @return int
     */
    private function getNextSort()
    {
        $max = $this->select('MAX(sort)')->fetchField();

        return ((int) $max) + 10;
    }

    /**
     * Возвращает id текущего пользователя.
     *
     * @return int|null
     */
    private function getContactId()
    {
        $user = wa()->getUser();

        return $user ? (int) $user->getId() : null;
    }
}
