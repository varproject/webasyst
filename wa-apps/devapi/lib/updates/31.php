<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2023 waResearchLab
 */
$model = new waModel();
$schema_file = wa()->getAppPath('lib/config/db.php', 'devapi');
if (file_exists($schema_file) && is_readable($schema_file)) {
    $tables = include($schema_file);
    foreach ($tables as $table => $data) {
        try {
            $model->query('select * from ' . $table . ' limit 1');
        } catch (waDbException $e) {
            $model->createSchema([$table => $tables[$table]]);
        }
        foreach ($data as $field => $db_schema) {
            $query = <<<SQL
select $field from $table limit 1
SQL;
            try {
                $model->query($query);
            } catch (waDbException $e) {
                $model->addColumn($field, $tables, null, $table);
            }
        }
    }
} else {
    throw new waException("Файл схемы таблиц БД $schema_file недоступен");
}
