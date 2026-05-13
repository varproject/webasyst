<?php

/**
 * Модификатор для Smarty, позволяющий выводить массивы в удобном формате.
 *
 * @param mixed $input Входные данные
 * @return string Отформатированный вывод
 */
if (!function_exists('smarty_modifier_dd')) {
    function smarty_modifier_dd($input)
    {
        return '<pre>' . print_r($input, true) . '</pre>';
    }
}


/**
 * Модификатор для склонение слова по числу с опцией включения числа в результат.
 * Примеры использования:
 *   {$count|ending:'товар, товара, товаров'}       → число + слово
 *   {$count|ending:'товар, товара, товаров':false} → только слово
 */
if (!function_exists('smarty_modifier_ending')) {
    function smarty_modifier_ending($number, $forms_string, $with_number = true)
    {
        $forms = array_map('trim', explode(',', $forms_string));

        // Защита: если форм меньше 3 — дублируем последнюю
        while (count($forms) < 3) {
            $forms[] = end($forms);
        }

        $abs = abs((int)$number);
        $n100 = $abs % 100;
        $n10  = $abs % 10;

        if ($n100 >= 11 && $n100 <= 14) {
            $word = $forms[2];
        } elseif ($n10 == 1) {
            $word = $forms[0];
        } elseif ($n10 >= 2 && $n10 <= 4) {
            $word = $forms[1];
        } else {
            $word = $forms[2];
        }

        return $with_number ? "{$number} {$word}" : $word;
    }
}


/**
 * Модификатор Smarty: человекочитаемое название статуса.
 *
 * Примеры использования в шаблоне:
 *   {$order.status|cabinet_status_name}
 *   {$order.status|cabinet_status_name:"Неизвестный статус"}
 *
 * @param string $status  Код статуса (например, "delivering", "new")
 * @param string $default Текст по умолчанию, если статус не найден
 *
 * @return string
 */
if (!function_exists('smarty_modifier_cabinet_status_name')) {
    function smarty_modifier_cabinet_status_name($status, $default = '')
    {
        $status = (string) $status;

        if ($status === '') {
            return $default;
        }

        // Карта статусов: код → человекочитаемое название
        $map = [
            'new'           => 'Новый',
            'processing'    => 'В обработке',
            'delivering'    => 'Доставляется',
            'dekivering'    => 'Доставляется',
            'delivered'     => 'Доставлен',
            'cancelled'     => 'Отменён',
            'returned'      => 'Возврат',
            'draft'         => 'Черновик',
            'completed'     => 'Черновик',
        ];

        if (array_key_exists($status, $map)) {
            return $map[$status];
        }

        // Если нет в карте — либо default, либо сам код статуса
        return ($default !== '') ? $default : $status;
    }
}
