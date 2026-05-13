<?php

/**
 * apanelProductService
 *
 * Назначение:
 * - создавать глобальный товар;
 * - обновлять глобальный товар;
 * - полностью удалять глобальный товар из базы.
 *
 * Зависимости:
 * - apanelProductModel.
 *
 * Инварианты:
 * - code обязателен;
 * - code уникален в apanel_product;
 * - полное удаление товара выполняется только отдельной операцией;
 * - отвязка от каталога не должна использовать этот сервис удаления.
 */
class apanelProductService
{
    /**
     * @var apanelProductModel
     */
    private $product_model;

    public function __construct()
    {
        $this->product_model = new apanelProductModel();
    }

    /**
     * Создаёт глобальный товар.
     *
     * @param array $data
     * @return int
     * @throws waException
     * @throws Exception
     */
    public function create(array $data): int
    {
        $code = $this->normalizeCode(ifset($data['code'], ''));
        $article = $this->normalizeNullableString(ifset($data['article'], null));
        $now = date('Y-m-d H:i:s');
        $contact_id = $this->getContactId();

        if ($code === '') {
            throw new waException('Не указан код товара.');
        }

        if ($this->product_model->codeExists($code)) {
            throw new waException('Товар с таким кодом уже существует.');
        }

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

        return (int) $product['id'];
    }

    /**
     * Обновляет глобальный товар.
     *
     * @param int $product_id
     * @param array $data
     * @return void
     * @throws waException
     * @throws Exception
     */
    public function update(int $product_id, array $data): void
    {
        $product = $this->product_model->getById($product_id);

        if (!$product) {
            throw new waException('Товар не найден.');
        }

        $update = [];

        if (array_key_exists('code', $data)) {
            $code = $this->normalizeCode($data['code']);

            if ($code === '') {
                throw new waException('Не указан код товара.');
            }

            if ($this->product_model->codeExists($code, $product_id)) {
                throw new waException('Товар с таким кодом уже существует.');
            }

            $update['code'] = $code;
        }

        if (array_key_exists('article', $data)) {
            $update['article'] = $this->normalizeNullableString($data['article']);
        }

        if (!$update) {
            return;
        }

        $update['updated_contact_id'] = $this->getContactId();
        $update['updated_datetime']   = date('Y-m-d H:i:s');

        $this->product_model->updateById($product_id, $update);
    }

    /**
     * Полностью удаляет глобальный товар.
     *
     * @param int $product_id
     * @return void
     * @throws waException
     * @throws Exception
     */
    public function delete(int $product_id): void
    {
        $product = $this->product_model->getById($product_id);

        if (!$product) {
            throw new waException('Товар не найден.');
        }

        $this->product_model->exec('START TRANSACTION');

        try {
            $this->product_model->deleteByField([
                'id' => $product_id,
            ]);

            $this->product_model->exec('COMMIT');
        } catch (Exception $e) {
            $this->product_model->exec('ROLLBACK');
            throw $e;
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
     * @return string|null
     */
    private function normalizeNullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
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
