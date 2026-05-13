<?php

class apanelCategoryService
{
    /**
     * @var apanelCatalogModel
     */
    private $catalog_model;

    /**
     * @var apanelCategoryModel
     */
    private $category_model;

    /**
     * @var apanelProductCategoriesModel
     */
    private $product_categories_model;

    public function __construct()
    {
        $this->catalog_model            = new apanelCatalogModel();
        $this->category_model           = new apanelCategoryModel();
        $this->product_categories_model = new apanelProductCategoriesModel();
    }

    /**
     * Добавление категории.
     *
     * @param int $catalog_id
     * @param array $data
     * @return int
     * @throws waException
     * @throws Exception
     */
    public function create(int $catalog_id, array $data): int
    {
        $catalog = $this->catalog_model->getById($catalog_id);

        if (!$catalog) {
            throw new waException('Каталог не найден.');
        }

        $name       = $this->normalizeRequiredName($data['name'] ?? '');
        $parent_id  = $this->normalizeNullableId($data['parent_id'] ?? null);
        $sort       = array_key_exists('sort', $data)
            ? $this->normalizeInt($data['sort'])
            : $this->getNextSort($catalog_id, $parent_id);
        $is_enabled = array_key_exists('is_enabled', $data)
            ? $this->normalizeBoolInt($data['is_enabled'])
            : 1;

        if ($parent_id !== null) {
            $parent = $this->category_model->getByCatalogAndId($catalog_id, $parent_id);

            if (!$parent) {
                throw new waException('Родительская категория не найдена в этом каталоге.');
            }
        }

        $now        = date('Y-m-d H:i:s');
        $contact_id = $this->getContactId();

        $category_id = (int) $this->category_model->insert([
            'catalog_id'         => $catalog_id,
            'parent_id'          => $parent_id,
            'name'               => $name,
            'sort'               => $sort,
            'is_enabled'         => $is_enabled,
            'created_contact_id' => $contact_id,
            'updated_contact_id' => $contact_id,
            'created_datetime'   => $now,
            'updated_datetime'   => $now,
        ]);

        if ($category_id <= 0) {
            throw new waException('Не удалось создать категорию.');
        }

        return $category_id;
    }

    /**
     * Редактирование категории.
     *
     * @param int $category_id
     * @param array $data
     * @return void
     * @throws waException
     * @throws Exception
     */
    public function update(int $category_id, array $data): void
    {
        $category = $this->category_model->getById($category_id);

        if (!$category) {
            throw new waException('Категория не найдена.');
        }

        $catalog_id = (int) $category['catalog_id'];
        $update     = [];

        if (array_key_exists('name', $data)) {
            $update['name'] = $this->normalizeRequiredName($data['name']);
        }

        if (array_key_exists('parent_id', $data)) {
            $parent_id = $this->normalizeNullableId($data['parent_id']);

            if ($parent_id !== null) {
                if ($parent_id === $category_id) {
                    throw new waException('Категория не может быть родителем самой себе.');
                }

                $parent = $this->category_model->getByCatalogAndId($catalog_id, $parent_id);

                if (!$parent) {
                    throw new waException('Родительская категория не найдена в этом каталоге.');
                }

                $this->assertNoCycle($category_id, $parent_id, $catalog_id);
            }

            $update['parent_id'] = $parent_id;
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

        $this->category_model->updateById($category_id, $update);
    }

    /**
     * Удаление категории.
     *
     * Стартовый безопасный вариант:
     * - можно удалить только лист;
     * - если у категории есть дочерние категории, удаление запрещено.
     *
     * @param int $category_id
     * @return void
     * @throws waException
     * @throws Exception
     */
    public function delete(int $category_id): void
    {
        $category = $this->category_model->getById($category_id);

        if (!$category) {
            throw new waException('Категория не найдена.');
        }

        if ($this->hasChildren($category_id)) {
            throw new waException('Нельзя удалить категорию, у которой есть дочерние категории.');
        }

        $this->category_model->exec('START TRANSACTION');

        try {
            $this->product_categories_model->deleteByField([
                'catalog_id'  => (int) $category['catalog_id'],
                'category_id' => $category_id,
            ]);

            $this->category_model->deleteByField([
                'id' => $category_id,
            ]);

            $this->category_model->exec('COMMIT');
        } catch (Exception $e) {
            $this->category_model->exec('ROLLBACK');
            throw $e;
        }
    }

    /**
     * @param int $catalog_id
     * @param int|null $parent_id
     * @return int
     */
    private function getNextSort(int $catalog_id, ?int $parent_id): int
    {
        $sql = "SELECT MAX(`sort`)
                FROM `apanel_category`
                WHERE `catalog_id` = i:catalog_id";

        $params = [
            'catalog_id' => $catalog_id,
        ];

        if ($parent_id === null) {
            $sql .= " AND `parent_id` IS NULL";
        } else {
            $sql .= " AND `parent_id` = i:parent_id";
            $params['parent_id'] = $parent_id;
        }

        $max = $this->category_model->query($sql, $params)->fetchField();

        return ((int) $max) + 10;
    }

    /**
     * @param int $category_id
     * @return bool
     */
    private function hasChildren(int $category_id): bool
    {
        $sql = "SELECT COUNT(*)
                FROM `apanel_category`
                WHERE `parent_id` = i:parent_id";

        return (int) $this->category_model->query($sql, [
            'parent_id' => $category_id,
        ])->fetchField() > 0;
    }

    /**
     * @param int $category_id
     * @param int $parent_id
     * @param int $catalog_id
     * @return void
     * @throws waException
     */
    private function assertNoCycle(int $category_id, int $parent_id, int $catalog_id): void
    {
        $current_parent_id = $parent_id;

        while ($current_parent_id !== null) {
            if ($current_parent_id === $category_id) {
                throw new waException('Нельзя создать цикл в дереве категорий.');
            }

            $parent = $this->category_model->getByCatalogAndId($catalog_id, $current_parent_id);

            if (!$parent) {
                break;
            }

            $current_parent_id = $parent['parent_id'] !== null
                ? (int) $parent['parent_id']
                : null;
        }
    }

    /**
     * @param mixed $value
     * @return string
     * @throws waException
     */
    private function normalizeRequiredName($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new waException('Не указано название категории.');
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return int|null
     */
    private function normalizeNullableId($value): ?int
    {
        if ($value === null || $value === '' || (int) $value <= 0) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param mixed $value
     * @return int
     */
    private function normalizeInt($value): int
    {
        return (int) $value;
    }

    /**
     * @param mixed $value
     * @return int
     */
    private function normalizeBoolInt($value): int
    {
        return empty($value) ? 0 : 1;
    }

    /**
     * @return int|null
     */
    private function getContactId(): ?int
    {
        $user = wa()->getUser();

        return $user ? (int) $user->getId() : null;
    }
}
