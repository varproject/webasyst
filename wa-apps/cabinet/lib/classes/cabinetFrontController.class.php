<?php

/**
 * Фронт-контроллер приложения cabinet.
 *
 * Расширяет стандартный waFrontController и:
 *  - назначает общий backend-layout и входной шаблон для всех HTML-контроллеров;
 *  - формирует служебные URL и массив сегментов backend-пути;
 *  - прокидывает общий контекст (модуль, экшен, меню и пр.) в waRequest и во View.
 */
class cabinetFrontController extends waFrontController
{
    /** @var array Сегменты текущего backend-URL (webasyst/cabinet/...) */
    protected $backend_uris;

    /** @var string Текущий backend-модуль (dashboard, selling, catalog, ...) */
    protected $backend_module;

    /** @var string Текущий backend-экшен (default, orders, ...) */
    protected $backend_action;

    /** @var array Системные модули, которые обрабатываются отдельно (дизайн, плагины и т.п.) */
    protected $system_modules = ['design', 'plugins'];

    /** @var string Полный backend-URL до панели webasyst, без app (…/webasyst/) */
    protected $backend_full_url;

    /** @var string Полный backend-URL до приложения cabinet (…/webasyst/cabinet/) */
    protected $backend_full_app_url;

    /** @var cabinetMenuService Сервис работы с меню B2B-кабинета */
    protected $menu_service;

    /**
     * Точка входа для запуска контроллера приложения.
     *
     * Здесь:
     *  - для backend-окружения навешиваем общий layout и входной шаблон;
     *  - собираем служебные URL и сегменты пути;
     *  - записываем параметры в waRequest::param();
     *  - назначаем глобальные переменные во View.
     *
     * @param waController $controller Контроллер, подобранный waFrontController
     * @param mixed        $params     Параметры для run()
     * @return mixed
     * @throws waException
     */
    protected function runController($controller, $params = null)
    {
        // Фронтенд приложения обрабатываем стандартным образом
        if ($this->system->getEnv() !== 'backend') {
            return parent::runController($controller, $params);
        }

        // Назначаем общий layout и входной шаблон для всех backend-контроллеров,
        // которые умеют работать с layout'ами и шаблонами (view-контроллеры).
        if (method_exists($controller, 'setLayout')) {
            if ($controller instanceof waViewActions || $controller instanceof waViewController) {
                $controller->setLayout(new cabinetBackendLayout());
                $controller->setTemplate(wa()->getAppPath('templates/actions/backend/main.html', 'cabinet'));
            }
        }

        // Инициализация shop (модели, конфигурация и т.п. будут доступны в рамках запроса), только для указанных модулей
        if (wa()->appExists('shop') && in_array($this->backend_module, ['selling', 'catalog'])) {
            wa('shop');
        }

        // Текущий модуль/экшен, определённые роутингом
        $this->backend_module = waRequest::param('module', 'dashboard');
        $this->backend_action = waRequest::param('action', 'default');

        // Безопасные сегменты backend-URL (webasyst/cabinet/... → ['webasyst','cabinet',...])
        $this->backend_uris = $this->buildSafeUrlSegments();

        // Полные URL до /webasyst/ и /webasyst/cabinet/
        $this->backend_full_url     = wa()->getRootUrl(true) . $this->backend_uris[0] . '/';
        $this->backend_full_app_url = wa()->getRootUrl(true) . $this->backend_uris[0] . '/' . $this->backend_uris[1] . '/';

        // Сервис меню B2B-кабинета
        $this->menu_service = new cabinetMenuService();

        // Служебные параметры, доступные через waRequest::param() во всех слоях
        waRequest::setParam('request_host', wa()->getConfig()->getHostUrl());
        waRequest::setParam('backend_url', wa()->getConfig()->getBackendUrl());
        waRequest::setParam('backend_app_url', wa('cabinet')->getAppUrl(null, true));
        waRequest::setParam('backend_full_url', $this->backend_full_url);
        waRequest::setParam('backend_full_app_url', $this->backend_full_app_url);
        waRequest::setParam('backend_uris', $this->backend_uris);

        // Глобальные переменные для layout и шаблонов экшенов
        wa()->getView()->assign([
            'module'          => $this->backend_module,
            'action'          => $this->backend_action,
            'system_modules'  => $this->system_modules,
            'backend_uris'    => $this->backend_uris,

            'cabinet_menu'    => $this->menu_service->getMainMenu(),
            'cabinet_submenu' => $this->menu_service->getSubmenu(),

            // Настройки динамического подключения шаблонов модуля/экшена.
            // Экшен может переопределить эти значения через $this->view->assign().

            // Сайдбар модуля. По умолчанию включен для всех (шаблон <модуль>/<модуль>Sidebar.html).
            'action_sidebar_tpl_mode' => true,

            // Пользовательский путь к шаблону сайдбара (если false — используется путь по умолчанию).
            'action_sidebar_tpl_path' => false,

            // Включение отдельного шаблона для конкретного экшена модуля
            // (например, для statusesAction()).
            'action_tpl_mode' => false,

            // Пользовательский шаблон для экшена (если false — используется путь по умолчанию
            // вида "<модуль>/<экшен>.html" или другая твоя схема).
            'action_tpl_path' => false,
        ]);

        // Дальше идёт обычный запуск контроллера
        return parent::runController($controller, $params);
    }




    /**
     * Превращает текущий URL (без query-строки) в безопасный массив сегментов.
     *
     * Пример:
     *   webasyst/cabinet/pages/645645/  → ['webasyst','cabinet','pages','645645']
     *
     * Правила:
     *   - обрезаем слеши по краям;
     *   - удаляем пустые сегменты;
     *   - в каждом сегменте оставляем только [a-zA-Z0-9_-];
     *   - сегменты, полностью состоящие из «мусора», отбрасываем.
     *
     * @return array
     */
    protected function buildSafeUrlSegments(): array
    {
        // Текущий URL без QueryString
        $url = wa()->getRouting()->getCurrentUrl(); // например: webasyst/cabinet/pages/645645/

        // Убираем слеши по краям
        $url = trim($url, '/');

        // Разбиваем на сегменты
        $parts = explode('/', $url);

        $safe = [];

        foreach ($parts as $p) {

            // Пропускаем пустые сегменты (двойные слеши и т.п.)
            if ($p === '') {
                continue;
            }

            // Разрешаем только буквы, цифры, "_", "-"
            $clean = preg_replace('/[^a-zA-Z0-9_\-]/u', '', $p);

            if ($clean === '') {
                continue;
            }

            $safe[] = $clean;
        }

        return $safe;
    }
}
