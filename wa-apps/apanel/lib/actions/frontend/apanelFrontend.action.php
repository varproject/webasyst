<?php

/**
 * apanelFrontendAction
 *
 * Главная frontend-точка витрины Apanel.
 *
 * Назначение:
 * - собрать runtime текущей витрины;
 * - проверить доступ;
 * - проверить выбранный plugin-продукт;
 * - определить текущий plugin route;
 * - определить текущий screen/action/params;
 * - отрендерить общий shell Apanel и контент plugin screen/action.
 *
 * Инварианты:
 * - action не выбирает Webasyst theme;
 * - action не использует storefront template/scheme/scenario;
 * - content берётся из выбранного plugin screen;
 * - template path plugin не может содержать переходы наверх;
 * - если plugin не выбран — показывается понятное состояние.
 */
class apanelFrontendAction extends waViewAction
{
    /**
     * Выполняет frontend-рендер витрины.
     *
     * @return void
     * @throws waException
     */
    public function execute()
    {
        $runtime = new apanelStorefrontRuntime();
        $data = $runtime->build();

        if (empty($data['access_result']['allowed'])) {
            throw new waException('Доступ к витрине запрещён.', 403);
        }

        if (ifset($data['plugin_status'], '') === 'missing') {
            throw new waException('Плагин витрины недоступен.', 503);
        }

        if (ifset($data['plugin_status'], '') === 'ready' && empty($data['screen'])) {
            throw new waException(_ws('Page not found'), 404);
        }

        $data['content'] = $this->renderContent($data);

        $this->view->assign($data);
        $this->setTemplate('frontend/Frontend.html', true);

        dd(waRequest::param());
    }

    /**
     * Рендерит контент выбранного plugin screen/action или системное состояние витрины.
     *
     * @param array $data Runtime-данные.
     * @return string
     */
    protected function renderContent($data)
    {
        if (ifset($data['plugin_status'], '') === 'empty') {
            return '<div class="apanel-empty-state"><h1>Плагин не выбран</h1><p>Для этой витрины пока не выбран функциональный плагин.</p></div>';
        }

        $screen = ifset($data['screen'], []);

        if (!$screen) {
            return '<div class="apanel-empty-state"><h1>Экран не найден</h1></div>';
        }

        $template = $this->getScreenTemplate($screen, ifset($data['route'], []));
        $plugin_id = trim((string) ifset($screen['plugin'], ''));

        if ($template === '' || $plugin_id === '' || strpos($template, '..') !== false) {
            return $this->renderFallbackScreen($screen, $data);
        }

        try {
            $path = rtrim(wa('apanel')->getConfig()->getPluginPath($plugin_id), '/') . '/templates/' . ltrim($template, '/');
        } catch (Exception $e) {
            return $this->renderFallbackScreen($screen, $data);
        }

        if (!file_exists($path)) {
            return $this->renderFallbackScreen($screen, $data);
        }

        $view = wa()->getView();
        $view->assign($data);

        return $view->fetch($path);
    }

    /**
     * Возвращает template текущего screen/action.
     *
     * @param array $screen Screen.
     * @param array $route Plugin route.
     * @return string
     */
    protected function getScreenTemplate($screen, $route)
    {
        $route_template = trim((string) ifset($route['template'], ''));

        if ($route_template !== '') {
            return $route_template;
        }

        $action = trim((string) ifset($route['action'], 'default'));
        $actions = ifset($screen['actions'], []);

        if ($action !== '' && $action !== 'default' && is_array($actions) && !empty($actions[$action]['template'])) {
            return trim((string) $actions[$action]['template']);
        }

        if (is_array($actions) && !empty($actions['default']['template'])) {
            return trim((string) $actions['default']['template']);
        }

        return trim((string) ifset($screen['template'], ''));
    }

    /**
     * Рендерит резервный контент screen/action.
     *
     * @param array $screen Screen.
     * @param array $data Runtime-данные.
     * @return string
     */
    protected function renderFallbackScreen($screen, $data)
    {
        $name = htmlspecialchars((string) ifset($screen['name'], 'Экран витрины'), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars((string) ifset($screen['description'], ''), ENT_QUOTES, 'UTF-8');
        $action = htmlspecialchars((string) ifset($data['screen_action'], 'default'), ENT_QUOTES, 'UTF-8');

        return '<div class="apanel-screen"><h1>' . $name . '</h1>'
            . ($description !== '' ? '<p>' . $description . '</p>' : '')
            . '<p class="hint">Action: ' . $action . '</p>'
            . '</div>';
    }
}
