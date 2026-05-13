<?php

/**
 * apanelProductModel
 *
 * Назначение:
 * - работать с глобальной базой товаров;
 * - давать сервисам минимальные выборки по code, id и состоянию привязки к каталогам;
 * - не хранить каталожную бизнес-логику.
 *
 * Зависимости:
 * - waModel.
 *
 * Инварианты:
 * - таблица модели: apanel_product;
 * - code обязателен и уникален глобально;
 * - article не является глобально уникальным;
 * - товар может существовать без единой связи с каталогами.
 *
 * Побочные эффекты:
 * - отсутствуют, кроме стандартных операций чтения/записи waModel.
 *
 * Ошибки:
 * - ошибки SQL пробрасываются штатным механизмом waModel/waDbException.
 */
class apanelProductModel extends waModel
{
    /**
     * @var string
     */
    protected $table = 'apanel_product';

    /**
     * Возвращает товар по внутреннему коду.
     *
     * @param string $code
     * @return array<string, mixed>|null
     */
    public function getByCode(string $code): ?array
    {
        $row = $this->getByField('code', $code);

        return $row ?: null;
    }

    /**
     * Проверяет, занят ли код товара другим товаром.
     *
     * @param string $code
     * @param int $exclude_id
     * @return bool
     */
    public function codeExists(string $code, int $exclude_id = 0): bool
    {
        $sql = "SELECT COUNT(*) 
                FROM `{$this->table}` 
                WHERE `code` = s:code";

        $params = [
            'code' => $code,
        ];

        if ($exclude_id > 0) {
            $sql .= " AND `id` <> i:exclude_id";
            $params['exclude_id'] = $exclude_id;
        }

        return (int) $this->query($sql, $params)->fetchField() > 0;
    }

    /**
     * Возвращает все товары глобальной базы.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllOrdered(): array
    {
        $sql = "SELECT * FROM `{$this->table}` ORDER BY `id` DESC";

        return $this->query($sql)->fetchAll();
    }

    /**
     * Возвращает товары без привязки к каталогам.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getWithoutCatalogs(): array
    {
        $sql = "SELECT p.*
                FROM `{$this->table}` p
                LEFT JOIN `apanel_catalog_product` cp
                    ON cp.`product_id` = p.`id`
                WHERE cp.`product_id` IS NULL
                ORDER BY p.`id` DESC";

        return $this->query($sql)->fetchAll();
    }
}
