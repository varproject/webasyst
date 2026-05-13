<?php

if (!function_exists('dd')) {
    function dd($arr = [])
    {
        if (empty($arr)) {
            return;
        }

        echo '<pre>';
        print_r($arr);
        echo '</pre>';
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
            'dekivering'    => 'Доставляется', // если у тебя именно так приходит
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

/**
 * Smarty-функция: генерация случайной строки.
 *
 * Примеры:
 *   {cabinet_random}                → 6 символов по умолчанию
 *   {cabinet_random len=8}          → 8 символов
 *   {cabinet_random len=6 alphabet="0123456789"} → только цифры
 *
 * @param array                    $params
 * @param Smarty_Internal_Template $smarty
 * @return string
 */
if (!function_exists('smarty_modifier_cabinet_status_name')) {
    function smarty_function_cabinet_random($params, Smarty_Internal_Template $smarty)
    {
        $length = isset($params['len']) ? (int) $params['len'] : 6;
        if ($length <= 0) {
            $length = 6;
        }

        // Набор символов по умолчанию: цифры + латинские буквы без похожих символов
        $alphabet = isset($params['alphabet']) && $params['alphabet'] !== ''
            ? (string) $params['alphabet']
            : 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';

        $alphabet_length = strlen($alphabet);
        if ($alphabet_length === 0) {
            return '';
        }

        $result = '';

        for ($i = 0; $i < $length; $i++) {
            // random_int — безопаснее, чем mt_rand
            $index = random_int(0, $alphabet_length - 1);
            $result .= $alphabet[$index];
        }

        return $result;
    }
}
