<?php

/**
 * apanelCatalogProductService
 *
 * Назначение:
 * - создавать товар и сразу привязывать его к каталогу;
 * - привязывать существующий товар к каталогу;
 * - обновлять контекст товара внутри каталога;
 * - отвязывать товар от каталога.
 *
 * Зависимости:
 * - apanelProductModel;
 * - apanelCatalogModel;
 * - apanelCategoryModel;
 * - apanelCatalogProductModel;
 * - apanelProductCategoriesModel.
 *
 * Инварианты:
 * - товар должен существовать в apanel_product;
 * - каталог должен существовать в apanel_catalog;
 * - связь (product_id, catalog_id) уникальна;
 * - категории можно назначать только внутри каталога этой связи;
 * - отвязка удаляет только контекст каталога и связи категорий этого каталога;
 * - отвязка никогда не удаляет глобальный товар.
 */
class apanelCatalogProductService
{
    /**
     * @var apanelProductModel
     */
    private $product_model;

    /**
     * @var apanelCatalogModel
     */
    private $catalog_model;

    /**
     * @var apanelCategoryModel
     */
    private $category_model;

    /**
     * @var apanelCatalogProductModel
     */
    private $catalog_product_model;

    /**
     * @var apanelProductCategoriesModel
     */
    private $product_categories_model;

    public function __construct()
    {
        $this->product_model            = new apanelProductModel();
        $this->catalog_model            = new apanelCatalogModel();
        $this->category_model           = new apanelCategoryModel();
        $this->catalog_product_model    = new apanelCatalogProductModel();
        $this->product_categories_model = new apanelProductCategoriesModel();
    }

    /**
     * Создаёт глобальный товар и сразу привязывает его к каталогу.
     *
     * @param int $catalog_id
     * @param array $product_data
     * @param array $catalog_product_data
     * @param array $category_ids
     * @return int
     * @throws waException
     * @throws Exception
     */
    public function createAndAttach(
        int $catalog_id,
        array $product_data,
        array $catalog_product_data,
        array $category_ids = []
    ): int {
        $catalog = $this->catalog_model->getById($catalog_id);

        if (!$catalog) {
            throw new waException('Каталог не найден.');
        }

        $code = $this->normalizeCode(ifset($product_data['code'], ''));
        $article = $this->normalizeNullableString(ifset($product_data['article'], null));
        $name = $this->normalizeRequiredName(ifset($catalog_product_data['name'], ''));
        $description = $this->normalizeNullableText(ifset($catalog_product_data['description'], null));
        $is_active = $this->normalizeBoolInt(ifset($catalog_product_data['is_active'], 1));
        $sort_order = $this->normalizeInt(ifset($catalog_product_data['sort_order'], 0));
        $category_ids = $this->normalizeCategoryIds($category_ids);
        $now = date('Y-m-d H:i:s');
        $contact_id = $this->getContactId();

        if ($code === '') {
            throw new waException('Не указан код товара.');
        }

        if ($this->product_model->codeExists($code)) {
            throw new waException('Товар с таким кодом уже существует.');
        }

        $this->assertCategoryIdsBelongToCatalog($catalog_id, $category_ids);

        $this->product_model->exec('START TRANSACTION');

        try {
            $this->product_model->insert([
                'code'               => $code,
                'article'            => $article,
                'created_contact_id' => $contact_id,
                'updated_contact_id' => $contact_id,
                'created_datetime'   => $now,
                'updated_datetime'   => $now,
            ]);

            $product = $this->product_model->getByCode($code);

            if (!$product) {
                throw new waException('Не удалось создать товар.');
            }

            $product_id = (int) $product['id'];

            $this->catalog_product_model->insert([
                'product_id'         => $product_id,
                'catalog_id'         => $catalog_id,
                'name'               => $name,
                'description'        => $description,
                'is_active'          => $is_active,
                'sort_order'         => $sort_order,
                'created_contact_id' => $contact_id,
                'updated_contact_id' => $contact_id,
                'created_datetime'   => $now,
                'updated_datetime'   => $now,
            ]);

            $this->insertCategoryLinks($product_id, $catalog_id, $category_ids);

            $this->product_model->exec('COMMIT');

            return $product_id;
        } catch (Exception $e) {
            $this->product_model->exec('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Привязывает существующий товар к каталогу.
     *
     * @param int $product_id
     * @param int $catalog_id
     * @param array $data
     * @param array $category_ids
     * @return void
     * @throws waException
     * @throws Exception
     */
    public function attach(int $product_id, int $catalog_id, array $data, array $category_ids = []): void
    {
        $product = $this->product_model->getById($product_id);
        $catalog = $this->catalog_model->getById($catalog_id);

        if (!$product) {
            throw new waException('Товар не найден.');
        }

        if (!$catalog) {
            throw new waException('Каталог не найден.');
        }

        if ($this->catalog_product_model->exists($product_id, $catalog_id)) {
            throw new waException('Товар уже привязан к этому каталогу.');
        }

        $name = $this->normalizeRequiredName(ifset($data['name'], ''));
        $description = $this->normalizeNullableText(ifset($data['description'], null));
        $is_active = $this->normalizeBoolInt(ifset($data['is_active'], 1));
        $sort_order = $this->normalizeInt(ifset($data['sort_order'], 0));
        $category_ids = $this->normalizeCategoryIds($category_ids);
        $now = date('Y-m-d H:i:s');
        $contact_id = $this->getContactId();

        $this->assertCategoryIdsBelongToCatalog($catalog_id, $category_ids);

        $this->catalog_product_model->exec('START TRANSACTION');

        try {
            $this->catalog_product_model->insert([
                'product_id'         => $product_id,
                'catalog_id'         => $catalog_id,
                'name'               => $name,
                'description'        => $description,
                'is_active'          => $is_active,
                'sort_order'         => $sort_order,
                'created_contact_id' => $contact_id,
                'updated_contact_id' => $contact_id,
                'created_datetime'   => $now,
                'updated_datetime'   => $now,
            ]);

            $this->insertCategoryLinks($product_id, $catalog_id, $category_ids);

            $this->catalog_product_model->exec('COMMIT');
        } catch (Exception $e) {
            $this->catalog_product_model->exec('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Обновляет контекст товара внутри каталога.
     *
     * @param int $product_id
     * @param int $catalog_id
     * @param array $data
     * @return void
     * @throws waException
     * @throws Exception
     */
    public function update(int $product_id, int $catalog_id, array $data): void
    {
        $catalog_product = $this->catalog_product_model->getByProductAndCatalog($product_id, $catalog_id);

        if (!$catalog_product) {
            throw new waException('Связь товара с каталогом не найдена.');
        }

        $update = [];

        if (array_key_exists('name', $data)) {
            $update['name'] = $this->normalizeRequiredName($data['name']);
        }

        if (array_key_exists('description', $data)) {
            $update['description'] = $this->normalizeNullableText($data['description']);
        }

        if (array_key_exists('is_active', $data)) {
            $update['is_active'] = $this->normalizeBoolInt($data['is_active']);
        }

        if (array_key_exists('sort_order', $data)) {
            $update['sort_order'] = $this->normalizeInt($data['sort_order']);
        }

        if (!$update) {
            return;
        }

        $update['updated_contact_id'] = $this->getContactId();
        $update['updated_datetime']   = date('Y-m-d H:i:s');

        $this->catalog_product_model->updateByField([
            'product_id' => $product_id,
            'catalog_id' => $catalog_id,
        ], $update);
    }

    /**
     * Отвязывает товар от каталога.
     *
     * @param int $product_id
     * @param int $catalog_id
     * @return void
     * @throws waException
     * @throws Exception
     */
    public function detach(int $product_id, int $catalog_id): void
    {
        $catalog_product = $this->catalog_product_model->getByProductAndCatalog($product_id, $catalog_id);

        if (!$catalog_product) {
            throw new waException('Связь товара с каталогом не найдена.');
        }

        $this->catalog_product_model->exec('START TRANSACTION');

        try {
            $this->product_categories_model->deleteByProductAndCatalog($product_id, $catalog_id);
            $this->catalog_product_model->deleteByProductAndCatalog($product_id, $catalog_id);

            $this->catalog_product_model->exec('COMMIT');
        } catch (Exception $e) {
            $this->catalog_product_model->exec('ROLLBACK');
            throw $e;
        }
    }

    /**
     * @param int $product_id
     * @param int $catalog_id
     * @param array $category_ids
     * @return void
     * @throws waException
     * @throws Exception
     */
    private function insertCategoryLinks(int $product_id, int $catalog_id, array $category_ids): void
    {
        foreach ($category_ids as $index => $category_id) {
            $this->product_categories_model->insert([
                'product_id'  => $product_id,
                'catalog_id'  => $catalog_id,
                'category_id' => $category_id,
                'sort_order'  => $index * 10,
            ]);
        }
    }

    /**
     * @param int $catalog_id
     * @param array $category_ids
     * @return void
     * @throws waException
     */
    private function assertCategoryIdsBelongToCatalog(int $catalog_id, array $category_ids): void
    {
        foreach ($category_ids as $category_id) {
            $category = $this->category_model->getByCatalogAndId($catalog_id, $category_id);

            if (!$category) {
                throw new waException('Передана категория чужого каталога или категория не существует.');
            }
        }
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function normalizeCode($value): string
    {
        return trim((string) $value);
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
            throw new waException('Не указано название товара в каталоге.');
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private function normalizeNullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private function normalizeNullableText($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
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
     * @param mixed $value
     * @return int
     */
    private function normalizeInt($value): int
    {
        return (int) $value;
    }

    /**
     * @param array $category_ids
     * @return array
     */
    private function normalizeCategoryIds(array $category_ids): array
    {
        $result = [];

        foreach ($category_ids as $category_id) {
            $category_id = (int) $category_id;

            if ($category_id <= 0) {
                continue;
            }

            $result[$category_id] = $category_id;
        }

        return array_values($result);
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
