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
 * Ядро работы с настройками приложения apanel.
 *
 * Класс работает напрямую с собственной таблицей apanel_settings,
 * хранит настройки в формате key/value и поддерживает вложенные значения
 * внутри JSON-массивов через путь вида settings.section.key.
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
 * Основная логика:
 * - первый сегмент пути — имя настройки в apanel_settings;
 * - остальные сегменты пути — ключи внутри массив-настройки;
 * - настройки один раз читаются из БД и кэшируются на время текущего запроса;
 * - save() и delete() автоматически обновляют runtime-кэш;
 * - массивы сохраняются в JSON;
 * - JSON-массивы при чтении возвращаются как PHP-массивы;
 * - get() может вернуть обычный вложенный массив либо плоскую карту;
 * - плоская карта строится рекурсивно через объединение вложенных ключей символом подчеркивания;
 * - для плоской карты можно указать общий префикс ключей;
 * - для плоской карты можно указать ключи, ветки которых нужно полностью пропустить;
 * - если ключ существует — значение обновляется;
 * - если ключа нет — значение добавляется;
 * - если $replace = false и старое/новое значение являются массивами — данные объединяются через array_replace_recursive();
 * - если $replace = true — значение полностью заменяется.
 *
 * Публичные методы:
 * - get($path = null, $default = null, $response = null) — получить все настройки, одну настройку, значение по пути или несколько значений;
 * - save($path, $value, $replace = false) — сохранить или обновить настройку либо значение по пути;
 * - delete($path = null, $keys = null) — удалить все настройки, одну настройку, значение по пути или список ключей внутри пути.
 *
 * Логика get():
 * - get() без аргументов возвращает все настройки текущей области;
 * - get('name', $default) возвращает одну настройку;
 * - get('name.inner.key', $default) возвращает вложенное значение внутри JSON-массива;
 * - get(['key1', 'key2'], $default) возвращает несколько значений по указанным путям;
 * - если $response = true — результат возвращается в виде плоской карты;
 * - если $response = [true, 'prefix', 'skip1, skip2'] — результат возвращается в виде плоской карты с префиксом и пропуском указанных веток;
 * - если $response = [false] или null — результат возвращается как есть;
 * - если $default не массив — он используется как общий дефолт для всех отсутствующих ключей;
 * - если $default массив — дефолты берутся по имени ключа или по порядковому индексу;
 * - если дефолт для отсутствующего ключа равен null — ключ не добавляется в результат;
 * - false, '', [] и другие значения возвращаются как обычные дефолты.
 *
 * Формат третьего аргумента get():
 * - true — включить плоский режим без префикса и без пропусков;
 * - false|null — вернуть результат как есть;
 * - [true] — включить плоский режим;
 * - [true, 'sidebar'] — включить плоский режим и добавить префикс sidebar_;
 * - [true, 'sidebar', 'items, footer'] — включить плоский режим, добавить префикс sidebar_ и пропустить ветки items и footer.
 *
 * Пример плоского чтения:
 * apanelSettings::get('ui.backend.sidebar', null, [
 *     true,
 *     'sidebar',
 *     'items, footer',
 * ]);
 *
 * Результат:
 * - sidebar_enabled => 1
 * - sidebar_logo_target_url => ''
 * - sidebar_logo_text => 'ERP: Торговля 1.0'
 * - sidebar_logo_img_enabled => 1
 * - sidebar_logo_img_file_name => 'favicon-apanel.png'
 *
 * Примеры чтения:
 * apanelSettings::get();
 * apanelSettings::get(null, null, true);
 * apanelSettings::get('navigations', []);
 * apanelSettings::get('navigations', [], true);
 * apanelSettings::get('ui.backend.sidebar', null, [true, 'sidebar']);
 * apanelSettings::get('ui.backend.sidebar', null, [true, 'sidebar', 'items, footer']);
 * apanelSettings::get('navigations.catalog.action');
 * apanelSettings::get('navigations.order/<id>.action');
 *
 * Примеры записи:
 * apanelSettings::save('backend_title', 'Панель управления');
 * apanelSettings::save('navigations', self::$navigations);
 * apanelSettings::save('navigations.catalog.action', 'myCatalog');
 * apanelSettings::save('navigations.catalog', ['name' => 'Каталог товаров']);
 * apanelSettings::save('navigations.catalog', ['id' => 'catalog', 'name' => 'Каталог'], true);
 *
 * Примеры удаления:
 * apanelSettings::delete();
 * apanelSettings::delete('navigations');
 * apanelSettings::delete('navigations.catalog');
 * apanelSettings::delete('navigations.catalog.action');
 * apanelSettings::delete('navigations.catalog', ['name', 'action']);
 */
final class apanelSettings extends waModel
{
    const SCOPE = 'app';
    const SCOPE_ID = '';

    protected $table = 'apanel_settings';

    protected static $instance;
    protected static $cache;


    // Получить все настройки, одну настройку, несколько настроек или значение по пути.
    public static function get($path = null, $default = null, $response = null)
    {
        self::loadCache();

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
            $result = self::$cache;

            return $flat ? self::flatten($result, $prefix, $skip) : $result;
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

                $value = self::get($item, $item_default);

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

        if (!array_key_exists($name, self::$cache)) {
            return $flat ? self::flatten($default, $prefix, $skip) : $default;
        }

        $value = self::$cache[$name];

        foreach ($parts as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return $flat ? self::flatten($default, $prefix, $skip) : $default;
            }

            $value = $value[$key];
        }

        return $flat ? self::flatten($value, $prefix, $skip) : $value;
    }

    // Сохранить или обновить настройку либо значение по пути.
    public static function save($path, $value, $replace = false)
    {
        self::loadCache();

        $parts = self::path($path);

        if (!$parts) {
            return false;
        }

        $name = array_shift($parts);

        if (!$parts) {
            if (!$replace && is_array($value) && array_key_exists($name, self::$cache) && is_array(self::$cache[$name])) {
                $value = array_replace_recursive(self::$cache[$name], $value);
            }

            return self::instance()->setRaw($name, self::encode($value));
        }

        $data = array_key_exists($name, self::$cache) ? self::$cache[$name] : [];

        if (!is_array($data)) {
            $data = [];
        }

        $ref  = &$data;
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

        return self::instance()->setRaw($name, self::encode($data));
    }

    // Удалить все настройки, одну настройку, значение по пути или указанные ключи внутри пути.
    public static function delete($path = null, $keys = null)
    {
        self::loadCache();

        if ($path === null) {
            return self::instance()->delRaw();
        }

        $parts = self::path($path);

        if (!$parts) {
            return false;
        }

        $name = array_shift($parts);

        if (!array_key_exists($name, self::$cache)) {
            return false;
        }

        if (!$parts && $keys === null) {
            return self::instance()->delRaw($name);
        }

        $data = self::$cache[$name];

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

            return self::instance()->setRaw($name, self::encode($data));
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

        return self::instance()->setRaw($name, self::encode($data));
    }




    // Получить экземпляр класса-модели.
    protected static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // Загрузить настройки в runtime-кэш текущего запроса.
    protected static function loadCache()
    {
        if (self::$cache !== null) {
            return;
        }

        self::$cache = [];

        foreach (self::instance()->getRaw() as $key => $value) {
            self::$cache[$key] = self::decode($value);
        }
    }

    // Получить одну сырую настройку или все сырые настройки.
    protected function getRaw($name = null, $default = null)
    {
        if ($name === null) {
            $sql = "SELECT name, value
                    FROM {$this->table}
                    WHERE scope = s:scope
                      AND scope_id = s:scope_id";

            $rows = $this->query($sql, [
                'scope'    => self::SCOPE,
                'scope_id' => self::SCOPE_ID,
            ])->fetchAll();

            $settings = [];

            foreach ($rows as $row) {
                $settings[$row['name']] = $row['value'];
            }

            return $settings;
        }

        $row = $this->getByField([
            'scope'    => self::SCOPE,
            'scope_id' => self::SCOPE_ID,
            'name'     => $name,
        ]);

        return $row ? $row['value'] : $default;
    }

    // Сохранить сырую настройку.
    protected function setRaw($name, $value)
    {
        $datetime = date('Y-m-d H:i:s');

        $exists = $this->getByField([
            'scope'    => self::SCOPE,
            'scope_id' => self::SCOPE_ID,
            'name'     => $name,
        ]);

        $data = [
            'scope'           => self::SCOPE,
            'scope_id'        => self::SCOPE_ID,
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
            self::$cache[$name] = self::decode($value);
        }

        return $result;
    }

    // Удалить одну сырую настройку или все настройки текущей области.
    protected function delRaw($name = null)
    {
        $params = [
            'scope'    => self::SCOPE,
            'scope_id' => self::SCOPE_ID,
        ];

        if ($name !== null) {
            $params['name'] = $name;
        }

        $result = $this->deleteByField($params);

        if ($result) {
            if ($name === null) {
                self::$cache = [];
            } else {
                unset(self::$cache[$name]);
            }
        }

        return $result;
    }

    // Подготовить значение для сохранения.
    protected static function encode($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    // Подготовить значение после чтения.
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

    // Разобрать путь настройки.
    protected static function path($path)
    {
        $path = trim((string) $path, '.');

        return $path === '' ? [] : explode('.', $path);
    }

    // Преобразовать вложенный массив в плоскую карту.
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

    // Подготовить список ключей, которые нужно пропустить при построении плоской карты.
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
}
