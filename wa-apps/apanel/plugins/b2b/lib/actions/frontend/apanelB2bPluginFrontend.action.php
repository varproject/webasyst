<?php

/**
 * apanelB2bPluginFrontendAction
 *
 * Базовый frontend action официального B2B plugin-продукта Apanel.
 *
 * Назначение:
 * - использовать штатный Webasyst dispatch для plugin action-классов;
 * - собрать runtime текущей витрины через apanelStorefrontRuntime;
 * - проверить, что текущая витрина действительно выбрала plugin b2b;
 * - отправить гостя на страницу входа, если B2B-витрина требует авторизацию;
 * - получить screen из декларации выбранного plugin;
 * - отрендерить screen template plugin-а внутри общего shell Apanel.
 *
 * Зависимости:
 * - waViewAction;
 * - apanelStorefrontRuntime;
 * - wa('apanel')->getConfig()->getPluginPath();
 * - ifset().
 *
 * Инварианты:
 * - routing выполняет Webasyst через plugins/b2b/lib/config/routing.php;
 * - этот класс не матчить URL и не парсит route вручную;
 * - business screen принадлежит plugin id b2b;
 * - disabled screen не рендерится;
 * - shell берётся из приложения Apanel;
 * - content берётся из templates/screens plugin-а.
 */
class apanelB2bPluginFrontendAction extends waViewAction
{
    /**
     * ID plugin-продукта.
     *
     * @var string
     */
    protected $plugin_id = 'b2b';

    /**
     * ID screen.
     *
     * @var string
     */
    protected $screen_id = '';

    /**
     * Выполняет frontend action.
     *
     * @return void
     * @throws waException
     */
    public function execute()
    {
        $runtime = new apanelStorefrontRuntime();
        $data = $runtime->build();

        $this->checkRuntime($data);

        $screen = $this->getScreen($data);
        $data['screen'] = $screen;
        $data['screen_id'] = $screen['id'];
        $data['screen_action'] = $this->getScreenAction();
        $data['screen_params'] = $this->getScreenParams();

        $this->prepareViewData($data, $screen);

        $data['content'] = $this->renderScreen($data, $screen);

        $this->view->assign($data);
        $this->setTemplate($this->getShellTemplate());
    }

    /**
     * Проверяет runtime текущей витрины.
     *
     * @param array $data Runtime-данные.
     * @return void
     * @throws waException
     */
    protected function checkRuntime($data)
    {
        if (ifset($data['plugin_status'], '') === 'missing') {
            throw new waException('Плагин витрины недоступен.', 503);
        }

        if ((string) ifset($data['plugin_id'], '') !== $this->plugin_id) {
            throw new waException(_ws('Page not found'), 404);
        }

        if (!$this->isAuthorized() && !empty($data['auth']['enabled'])) {
            $this->redirect($this->getLoginUrl($data));
        }

        if (!empty($data['access_result']['allowed'])) {
            return;
        }

        throw new waException('Доступ к витрине запрещён.', 403);
    }

    /**
     * Проверяет авторизацию frontend-пользователя.
     *
     * @return bool
     */
    protected function isAuthorized()
    {
        return wa()->getUser()->isAuth();
    }

    /**
     * Возвращает URL страницы входа.
     *
     * @param array $data Runtime-данные.
     * @return string
     */
    protected function getLoginUrl($data)
    {
        $url = $this->getStorefrontUrl($data, 'login/');
        $return_url = waRequest::server('REQUEST_URI', '', waRequest::TYPE_STRING_TRIM);

        return $url . ($return_url !== '' ? '?return=' . urlencode($return_url) : '');
    }

    /**
     * Возвращает URL внутри текущей витрины.
     *
     * @param array $data Runtime-данные.
     * @param string $path Внутренний путь.
     * @return string
     */
    protected function getStorefrontUrl($data, $path = '')
    {
        $base_url = ifset($data['storefront']['full_url'], wa()->getAppUrl('apanel'));
        $base_url = rtrim((string) $base_url, '/') . '/';
        $path = trim((string) $path, '/');

        return $base_url . ($path !== '' ? $path . '/' : '');
    }

    /**
     * Возвращает screen текущего action.
     *
     * @param array $data Runtime-данные.
     * @return array
     * @throws waException
     */
    protected function getScreen($data)
    {
        $screen_id = $this->getScreenId();
        $screens = is_array(ifset($data['screens'], [])) ? $data['screens'] : [];

        if ($screen_id === '' || empty($screens[$screen_id])) {
            throw new waException(_ws('Page not found'), 404);
        }

        $screen = $screens[$screen_id];

        if (empty($screen['enabled'])) {
            throw new waException(_ws('Page not found'), 404);
        }

        return $screen;
    }

    /**
     * Возвращает ID screen.
     *
     * @return string
     */
    protected function getScreenId()
    {
        return trim((string) $this->screen_id);
    }

    /**
     * Возвращает screen action.
     *
     * @return string
     */
    protected function getScreenAction()
    {
        return 'default';
    }

    /**
     * Возвращает параметры screen action.
     *
     * @return array
     */
    protected function getScreenParams()
    {
        return [];
    }

    /**
     * Подготавливает дополнительные данные для шаблона.
     *
     * @param array $data Runtime-данные.
     * @param array $screen Screen.
     * @return void
     */
    protected function prepareViewData(&$data, $screen)
    {
    }

    /**
     * Рендерит screen template plugin-а.
     *
     * @param array $data Runtime-данные.
     * @param array $screen Screen.
     * @return string
     */
    protected function renderScreen($data, $screen)
    {
        $template = $this->getScreenTemplate($screen);

        if ($template === '') {
            return $this->renderFallbackScreen($screen);
        }

        try {
            $path = rtrim(wa('apanel')->getConfig()->getPluginPath($this->plugin_id), '/') . '/templates/' . ltrim($template, '/');
        } catch (Exception $e) {
            return $this->renderFallbackScreen($screen);
        }

        if (!file_exists($path)) {
            return $this->renderFallbackScreen($screen);
        }

        $view = wa()->getView();
        $view->assign($data);

        return $view->fetch($path);
    }

    /**
     * Возвращает template текущего screen.
     *
     * @param array $screen Screen.
     * @return string
     */
    protected function getScreenTemplate($screen)
    {
        $template = trim((string) ifset($screen['template'], ''));

        if ($template === '' || strpos($template, '..') !== false) {
            return '';
        }

        return $template;
    }

    /**
     * Возвращает fallback HTML для screen без шаблона.
     *
     * @param array $screen Screen.
     * @return string
     */
    protected function renderFallbackScreen($screen)
    {
        $name = htmlspecialchars((string) ifset($screen['name'], 'Экран витрины'), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars((string) ifset($screen['description'], ''), ENT_QUOTES, 'UTF-8');

        return '<section class="apanel-screen"><h1>' . $name . '</h1>'
            . ($description !== '' ? '<p>' . $description . '</p>' : '')
            . '</section>';
    }

    /**
     * Возвращает общий shell template приложения Apanel.
     *
     * @return string
     */
    protected function getShellTemplate()
    {
        return wa('apanel')->getAppPath('templates/actions/frontend/Frontend.html');
    }
}