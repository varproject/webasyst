<?php

/**
 * Apanel
 *
 * @author Vagram Petrosian <var_project@mail.ru>
 * @copyright 2026 «Apanel»
 * @link http://wapv.ru
 *
 * apanelSettings
 *
 * Ядро работы с настройками приложения Apanel.
 *
 * Назначение:
 * - работать с таблицей apanel_settings в формате scope/scope_id/name/value;
 * - сохранять общие настройки приложения в scope=app, scope_id='';
 * - сохранять настройки конкретных витрин в отдельных scope/scope_id;
 * - поддерживать вложенные значения внутри JSON через путь вида group.inner.key;
 * - кэшировать настройки отдельно для каждой области scope/scope_id на время текущего запроса;
 * - сохранять массивы в JSON;
 * - возвращать JSON-массивы как PHP-массивы;
 * - поддерживать плоский режим get() через третий аргумент.
 *
 * Таблица apanel_settings:
 * - scope — область настройки;
 * - scope_id — ID области;
 * - name — верхний ключ настройки;
 * - value — строка или JSON;
 * - create_datetime — дата создания;
 * - update_datetime — дата изменения;
 * - contact_id — ID пользователя, который последним изменил настройку.
 *
 * Основная модель:
 * - app-настройки: scope=app, scope_id='', name={key};
 * - storefront-настройки: scope=storefront, scope_id={storefront_key}, name={group};
 * - первый сегмент path — name в таблице;
 * - остальные сегменты path — ключи внутри JSON-массива.
 *
 * Публичные методы app-области:
 * - get($path = null, $default = null, $response = null);
 * - save($path, $value, $replace = false);
 * - delete($path = null, $keys = null).
 *
 * Публичные методы scoped-области:
 * - getScoped($scope, $scope_id, $path = null, $default = null, $response = null);
 * - saveScoped($scope, $scope_id, $path, $value, $replace = false);
 * - deleteScoped($scope, $scope_id, $path = null, $keys = null).
 *
 * Инварианты:
 * - get/save/delete работают только с app scope;
 * - getScoped/saveScoped/deleteScoped работают с явно указанной областью;
 * - route/domain/url/full_url не должны храниться в settings витрины;
 * - storefront_key хранится в scope_id, а не внутри JSON;
 * - одна группа настроек витрины = одна строка таблицы;
 * - runtime-кэш не смешивает разные scope/scope_id.
 *
 * Побочные эффекты:
 * - save/saveScoped создают или обновляют строки apanel_settings;
 * - delete/deleteScoped удаляют строки или вложенные значения;
 * - после записи/удаления обновляется runtime-кэш текущего запроса.
 */
final class apanelSettings extends waModel
{
    const SCOPE = 'app';
    const SCOPE_ID = '';

    protected $table = 'apanel_settings';

    protected static $instance;
    protected static $cache = [];

    /**
     * Получает app-настройки.
     *
     * @param string|array|null $path Путь настройки.
     * @param mixed $default Значение по умолчанию.
     * @param mixed $response Режим ответа.
     * @return mixed
     */
    public static function get($path = null, $default = null, $response = null)
    {
        return self::getScoped(self::SCOPE, self::SCOPE_ID, $path, $default, $response);
    }

    /**
     * Сохраняет app-настройку.
     *
     * @param string $path Путь настройки.
     * @param mixed $value Значение.
     * @param bool $replace Полностью заменить значение.
     * @return bool|int
     */
    public static function save($path, $value, $replace = false)
    {
        return self::saveScoped(self::SCOPE, self::SCOPE_ID, $path, $value, $replace);
    }

    /**
     * Удаляет app-настройку.
     *
     * @param string|null $path Путь настройки.
     * @param string|array|null $keys Ключ или список ключей внутри пути.
     * @return bool|int
     */
    public static function delete($path = null, $keys = null)
    {
        return self::deleteScoped(self::SCOPE, self::SCOPE_ID, $path, $keys);
    }

    /**
     * Получает настройки указанной области.
     *
     * @param string $scope Область настроек.
     * @param string $scope_id ID области.
     * @param string|array|null $path Путь настройки.
     * @param mixed $default Значение по умолчанию.
     * @param mixed $response Режим ответа.
     * @return mixed
     */
    public static function getScoped($scope, $scope_id, $path = null, $default = null, $response = null)
    {
        $scope = self::normalizeScope($scope);
        $scope_id = self::normalizeScopeId($scope_id);

        self::loadCache($scope, $scope_id);

        $cache_key = self::cacheKey($scope, $scope_id);
        $cache = self::$cache[$cache_key];

        $flat = false;
        $prefix = '';
        $skip = [];

        if (is_array($response)) {
            $flat = !empty($response[0]);
            $prefix = isset($response[1]) ? trim((string) $response[1], '_') : '';
            $skip = isset($response[2]) ? self::skipKeys($response[2]) : [];
        } else {
            $flat = !empty($response);
        }

        if ($path === null) {
            return $flat ? self::flatten($cache, $prefix, $skip) : $cache;
        }

        if (is_array($path)) {
            $result = [];

            foreach ($path as $i => $item) {
                if (is_array($default) && array_key_exists($item, $default)) {
                    $item_default = $default[$item];
                } elseif (is_array($default) && array_key_exists($i, $default)) {
                    $item_default = $default[$i];
                } else {
                    $item_default = $default;
                }

                $value = self::getScoped($scope, $scope_id, $item, $item_default);

                if ($value === null) {
                    continue;
                }

                $result[$item] = $value;
            }

            return $flat ? self::flatten($result, $prefix, $skip) : $result;
        }

        $parts = self::path($path);

        if (!$parts) {
            return $flat ? self::flatten($default, $prefix, $skip) : $default;
        }

        $name = array_shift($parts);

        if (!array_key_exists($name, $cache)) {
            return $flat ? self::flatten($default, $prefix, $skip) : $default;
        }

        $value = $cache[$name];

        foreach ($parts as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return $flat ? self::flatten($default, $prefix, $skip) : $default;
            }

            $value = $value[$key];
        }

        return $flat ? self::flatten($value, $prefix, $skip) : $value;
    }

    /**
     * Сохраняет настройку указанной области.
     *
     * @param string $scope Область настроек.
     * @param string $scope_id ID области.
     * @param string $path Путь настройки.
     * @param mixed $value Значение.
     * @param bool $replace Полностью заменить значение.
     * @return bool|int
     */
    public static function saveScoped($scope, $scope_id, $path, $value, $replace = false)
    {
        $scope = self::normalizeScope($scope);
        $scope_id = self::normalizeScopeId($scope_id);

        self::loadCache($scope, $scope_id);

        $cache_key = self::cacheKey($scope, $scope_id);
        $parts = self::path($path);

        if (!$parts) {
            return false;
        }

        $name = array_shift($parts);

        if (!$parts) {
            if (
                !$replace
                && is_array($value)
                && array_key_exists($name, self::$cache[$cache_key])
                && is_array(self::$cache[$cache_key][$name])
            ) {
                $value = array_replace_recursive(self::$cache[$cache_key][$name], $value);
            }

            return self::instance()->setRaw($scope, $scope_id, $name, self::encode($value));
        }

        $data = array_key_exists($name, self::$cache[$cache_key]) ? self::$cache[$cache_key][$name] : [];

        if (!is_array($data)) {
            $data = [];
        }

        $ref = &$data;
        $last = array_pop($parts);

        foreach ($parts as $key) {
            if (!isset($ref[$key]) || !is_array($ref[$key])) {
                $ref[$key] = [];
            }

            $ref = &$ref[$key];
        }

        if (!$replace && isset($ref[$last]) && is_array($ref[$last]) && is_array($value)) {
            $ref[$last] = array_replace_recursive($ref[$last], $value);
        } else {
            $ref[$last] = $value;
        }

        return self::instance()->setRaw($scope, $scope_id, $name, self::encode($data));
    }

    /**
     * Удаляет настройку указанной области.
     *
     * @param string $scope Область настроек.
     * @param string $scope_id ID области.
     * @param string|null $path Путь настройки.
     * @param string|array|null $keys Ключ или список ключей внутри пути.
     * @return bool|int
     */
    public static function deleteScoped($scope, $scope_id, $path = null, $keys = null)
    {
        $scope = self::normalizeScope($scope);
        $scope_id = self::normalizeScopeId($scope_id);

        self::loadCache($scope, $scope_id);

        $cache_key = self::cacheKey($scope, $scope_id);

        if ($path === null) {
            return self::instance()->delRaw($scope, $scope_id);
        }

        $parts = self::path($path);

        if (!$parts) {
            return false;
        }

        $name = array_shift($parts);

        if (!array_key_exists($name, self::$cache[$cache_key])) {
            return false;
        }

        if (!$parts && $keys === null) {
            return self::instance()->delRaw($scope, $scope_id, $name);
        }

        $data = self::$cache[$cache_key][$name];

        if (!is_array($data)) {
            return false;
        }

        $ref = &$data;

        if ($keys !== null) {
            foreach ($parts as $key) {
                if (!isset($ref[$key]) || !is_array($ref[$key])) {
                    return false;
                }

                $ref = &$ref[$key];
            }

            $deleted = false;

            foreach ((array) $keys as $key) {
                if (array_key_exists($key, $ref)) {
                    unset($ref[$key]);
                    $deleted = true;
                }
            }

            if (!$deleted) {
                return false;
            }

            return self::instance()->setRaw($scope, $scope_id, $name, self::encode($data));
        }

        $last = array_pop($parts);

        foreach ($parts as $key) {
            if (!isset($ref[$key]) || !is_array($ref[$key])) {
                return false;
            }

            $ref = &$ref[$key];
        }

        if (!array_key_exists($last, $ref)) {
            return false;
        }

        unset($ref[$last]);

        return self::instance()->setRaw($scope, $scope_id, $name, self::encode($data));
    }

    /**
     * Получает экземпляр класса-модели.
     *
     * @return apanelSettings
     */
    protected static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Загружает настройки указанной области в runtime-кэш.
     *
     * @param string $scope Область настроек.
     * @param string $scope_id ID области.
     * @return void
     */
    protected static function loadCache($scope, $scope_id)
    {
        $cache_key = self::cacheKey($scope, $scope_id);

        if (array_key_exists($cache_key, self::$cache)) {
            return;
        }

        self::$cache[$cache_key] = [];

        foreach (self::instance()->getRaw($scope, $scope_id) as $key => $value) {
            self::$cache[$cache_key][$key] = self::decode($value);
        }
    }

    /**
     * Получает одну сырую настройку или все сырые настройки области.
     *
     * @param string $scope Область настроек.
     * @param string $scope_id ID области.
     * @param string|null $name Имя настройки.
     * @param mixed $default Значение по умолчанию.
     * @return mixed
     */
    protected function getRaw($scope, $scope_id, $name = null, $default = null)
    {
        if ($name === null) {
            $sql = "SELECT name, value
                    FROM {$this->table}
                    WHERE scope = s:scope
                      AND scope_id = s:scope_id";

            $rows = $this->query($sql, [
                'scope'    => $scope,
                'scope_id' => $scope_id,
            ])->fetchAll();

            $settings = [];

            foreach ($rows as $row) {
                $settings[$row['name']] = $row['value'];
            }

            return $settings;
        }

        $row = $this->getByField([
            'scope'    => $scope,
            'scope_id' => $scope_id,
            'name'     => $name,
        ]);

        return $row ? $row['value'] : $default;
    }

    /**
     * Сохраняет сырую настройку.
     *
     * @param string $scope Область настроек.
     * @param string $scope_id ID области.
     * @param string $name Имя настройки.
     * @param mixed $value Значение.
     * @return bool|int
     */
    protected function setRaw($scope, $scope_id, $name, $value)
    {
        $datetime = date('Y-m-d H:i:s');

        $exists = $this->getByField([
            'scope'    => $scope,
            'scope_id' => $scope_id,
            'name'     => $name,
        ]);

        $data = [
            'scope'           => $scope,
            'scope_id'        => $scope_id,
            'name'            => $name,
            'value'           => $value,
            'update_datetime' => $datetime,
            'contact_id'      => wa()->getUser()->getId(),
        ];

        if (!$exists) {
            $data['create_datetime'] = $datetime;
        }

        $result = $this->multipleInsert($data, [
            'value',
            'update_datetime',
            'contact_id',
        ]);

        if ($result) {
            $cache_key = self::cacheKey($scope, $scope_id);

            if (!array_key_exists($cache_key, self::$cache)) {
                self::$cache[$cache_key] = [];
            }

            self::$cache[$cache_key][$name] = self::decode($value);
        }

        return $result;
    }

    /**
     * Удаляет одну сырую настройку или все настройки указанной области.
     *
     * @param string $scope Область настроек.
     * @param string $scope_id ID области.
     * @param string|null $name Имя настройки.
     * @return bool|int
     */
    protected function delRaw($scope, $scope_id, $name = null)
    {
        $params = [
            'scope'    => $scope,
            'scope_id' => $scope_id,
        ];

        if ($name !== null) {
            $params['name'] = $name;
        }

        $result = $this->deleteByField($params);

        if ($result) {
            $cache_key = self::cacheKey($scope, $scope_id);

            if ($name === null) {
                self::$cache[$cache_key] = [];
            } elseif (isset(self::$cache[$cache_key])) {
                unset(self::$cache[$cache_key][$name]);
            }
        }

        return $result;
    }

    /**
     * Подготавливает значение для сохранения.
     *
     * @param mixed $value Значение.
     * @return mixed
     */
    protected static function encode($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * Подготавливает значение после чтения.
     *
     * @param mixed $value Значение из БД.
     * @return mixed
     */
    protected static function decode($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $value_trimmed = trim($value);

        if ($value_trimmed === '') {
            return $value;
        }

        $first = substr($value_trimmed, 0, 1);

        if ($first !== '[' && $first !== '{') {
            return $value;
        }

        $json = json_decode($value, true);

        return is_array($json) ? $json : $value;
    }

    /**
     * Разбирает путь настройки.
     *
     * @param string $path Путь.
     * @return array
     */
    protected static function path($path)
    {
        $path = trim((string) $path, '.');

        return $path === '' ? [] : explode('.', $path);
    }

    /**
     * Преобразует вложенный массив в плоскую карту.
     *
     * @param mixed $data Значение.
     * @param string $prefix Префикс ключей.
     * @param array $skip Пропускаемые ключи.
     * @return mixed
     */
    protected static function flatten($data, $prefix = '', $skip = [])
    {
        if (!is_array($data)) {
            return $data;
        }

        $result = [];

        foreach ($data as $key => $value) {
            $key = str_replace('.', '_', (string) $key);

            if (in_array($key, $skip, true)) {
                continue;
            }

            $name = $prefix === '' ? $key : $prefix . '_' . $key;

            if (is_array($value)) {
                if (!$value) {
                    $result[$name] = [];
                    continue;
                }

                $result += self::flatten($value, $name, $skip);
            } else {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    /**
     * Подготавливает список ключей, которые нужно пропустить при построении плоской карты.
     *
     * @param string|array $keys Ключи.
     * @return array
     */
    protected static function skipKeys($keys)
    {
        if (is_string($keys)) {
            $keys = explode(',', $keys);
        }

        $result = [];

        foreach ((array) $keys as $key) {
            $key = trim((string) $key);

            if ($key === '') {
                continue;
            }

            $result[] = str_replace('.', '_', $key);
        }

        return $result;
    }

    /**
     * Нормализует scope.
     *
     * @param string $scope Область.
     * @return string
     */
    protected static function normalizeScope($scope)
    {
        $scope = trim((string) $scope);

        return $scope === '' ? self::SCOPE : $scope;
    }

    /**
     * Нормализует scope_id.
     *
     * @param string $scope_id ID области.
     * @return string
     */
    protected static function normalizeScopeId($scope_id)
    {
        return trim((string) $scope_id);
    }

    /**
     * Собирает ключ runtime-кэша.
     *
     * @param string $scope Область.
     * @param string $scope_id ID области.
     * @return string
     */
    protected static function cacheKey($scope, $scope_id)
    {
        return $scope . "\n" . $scope_id;
    }
}
