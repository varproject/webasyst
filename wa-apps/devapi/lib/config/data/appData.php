<?php
$query = <<<SQL
select name, value from wa_app_settings where app_id="devapi" and name in ("app_name", "app_icon")
SQL;
$data = (new waModel())->query($query)->fetchAll('name', true);
$icon = [
    'icon' => [
        48 => 'img/' . ifset($data['app_icon'], 'devapi48.png')
    ],
    'name' => ifset($data['app_name'],'Разработчик Webasyst'),
    'link' => '',
    'rights' => 'backend',
    'img' => 'img/' . ifset($data['app_icon'], 'devapi48.png')
];
return ['devapi' => $icon];
