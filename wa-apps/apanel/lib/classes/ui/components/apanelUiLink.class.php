<?php

final class apanelUiLink
{
    public static function execute(array $params = []): string
    {
        // 1. Извлекаем текст (label) и иконку
        $label = $params['label'] ?? '';
        $icon = !empty($params['icon']) ? apanelGetIcon::html($params['icon']) : '';

        // 2. Обработка классов (склеиваем массив или строку)
        $classAttr = '';
        if (isset($params['class'])) {
            $classAttr = is_array($params['class'])
                ? implode(' ', array_unique($params['class']))
                : $params['class'];
        }

        // 3. Собираем атрибуты, специфичные для ссылки <a>
        // Добавляем href по умолчанию, если он не передан
        if (!isset($params['href'])) {
            $params['href'] = '#';
        }

        $allowedAttrs = ['id', 'name', 'href', 'target', 'title', 'style', 'rel', 'download'];
        $attributes = " class=\"" . htmlspecialchars($classAttr) . "\"";

        foreach ($params as $key => $value) {
            // Пропускаем массивы и служебные поля, оставляем только разрешенные атрибуты
            if (in_array($key, $allowedAttrs) && !is_array($value) && $value !== null) {
                $attributes .= sprintf(' %s="%s"', $key, htmlspecialchars((string)$value));
            }
        }

        // 4. Рендерим результат
        return <<<HTML
            <a{$attributes}>{$icon}{$label}</a>
        HTML;
    }
}

/* 
Пример использования с вашим массивом:
echo apanelUiLink::execute([
    'label' => 'Перейти в профиль',
    'href'  => '/profile/',
    'class' => ['link-primary', 'Apanel_ui_link'],
    'icon'  => 'bi bi-person'
]);
*/
