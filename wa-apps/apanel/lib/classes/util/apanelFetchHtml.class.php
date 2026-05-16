<?php

/**
 * apanelFetchHtml
 *
 * Назначение:
 * - рендерить Smarty-шаблон в HTML-строку;
 * - использовать отдельный экземпляр waSmarty3View для каждого вызова;
 * - возвращать готовый HTML для вставки в шаблоны, переменные и UI-блоки.
 *
 * Зависимости:
 * - waSmarty3View;
 * - waException;
 * - waSystem;
 * - ifset().
 *
 * Инварианты:
 * - каждый вызов stateless;
 * - метод всегда возвращает строку;
 * - путь к шаблону считается относительно wa-apps/apanel/.
 *
 * Побочные эффекты:
 * - выполняет компиляцию и рендеринг Smarty-шаблона.
 *
 * Ошибки:
 * - при отсутствии файла шаблона выбрасывается waException;
 * - исключения уровня Exception оборачиваются в waException;
 * - ошибки уровня Error и TypeError не скрываются.
 */
final class apanelFetchHtml
{
    /**
     * Возвращает результат рендеринга шаблона.
     *
     * Параметры:
     * - file   => путь к шаблону относительно wa-apps/apanel/;
     * - assign => массив переменных для шаблона.
     *
     * @param array<string, mixed> $params
     * @return string
     * @throws waException
     */
    public static function getHtml(array $params = []): string
    {
        $file_param = (string) ifset($params['file'], '');

        if ($file_param === '') {
            return '';
        }

        $file = wa()->getAppPath($file_param, 'apanel');

        if (!is_file($file)) {
            throw new waException("Файл шаблона не найден: {$file}");
        }

        try {
            // $view = new waSmarty3View(wa()); 
            // пока берем текщйи экземпляр, хз может конечно лучше на каждый свой, видно будет
            $view = waSystem::getInstance()->getView();
            
            $view->assign((array) ifset($params['assign'], []));

            return (string) $view->fetch($file);
        } catch (Exception $e) {
            throw new waException("Ошибка рендеринга шаблона {$file}: " . $e->getMessage(), 0, $e);
        }
    }
}
