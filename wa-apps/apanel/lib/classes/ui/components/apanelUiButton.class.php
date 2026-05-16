<?php

final class apanelUiButton
{
    public static function execute(array $params = []): string
    {
        // 1. Извлекаем контент (текст и иконку)
        $label = $params['label'] ?? '';
        $icon = !empty($params['icon']) ? apanelGetIcon::html($params['icon']) : '';

        // 2. Обрабатываем классы (склеиваем массив в строку)
        $class_attr = '';
        if (isset($params['class'])) {
            $class_attr = is_array($params['class'])
                ? implode(' ', array_unique($params['class']))
                : $params['class'];
        }

        // 3. Формируем список стандартных HTML-атрибутов
        // Оставляем только те, что реально нужны тегу <button>
        $allowed_attrs = ['id', 'name', 'type', 'style', 'value', 'title', 'disabled'];
        $attributes = " class=\"" . htmlspecialchars($class_attr) . "\"";

        foreach ($params as $key => $value) {
            if (in_array($key, $allowed_attrs) && !is_array($value) && $value !== null) {
                $attributes .= sprintf(' %s="%s"', $key, htmlspecialchars((string)$value));
            }
        }

        // Если type не передан, ставим по умолчанию button
        if (!str_contains($attributes, 'type=')) {
            $attributes .= ' type="button"';
        }

        // 4. Возвращаем готовую верстку
        return <<<HTML
            <span class="btn-wrapper d-flex align-items-center justify-content-center">
                <button{$attributes}>{$icon}{$label}</button>
            </span>
        HTML;
    }
}
