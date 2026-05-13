<?php

class cabinetMenuService
{
    /**
     * Закэшированная структура полного меню B2B.
     *
     * @var array|null
     */
    protected $menu;

    /**
     * Главное меню:
     *  - фильтрация по правам
     *  - подсветка активного пункта
     */
    public function getMainMenu(): array
    {
        $menu = $this->getMenu();
        $user = wa()->getUser();

        // Суперадмин или администратор приложения "cabinet" видит всё
        if (!$user->isAdmin() && !$user->isAdmin('cabinet')) {
            $rights = (array) $user->getRights('cabinet');

            // Фильтрация по permissions
            $menu = array_values(array_filter($menu, function (array $item) use ($rights): bool {
                if (empty($item['permissions'])) {
                    // Нет ограничений — пункт доступен всем,
                    // у кого есть доступ в приложение
                    return true;
                }

                foreach ($item['permissions'] as $perm) {
                    if (!empty($rights[$perm])) {
                        return true;
                    }
                }

                return false;
            }));
        }

        return $this->markMainActive($menu);
    }

    /**
     * Возвращает submenu текущего раздела.
     */
    public function getSubmenu(): ?array
    {
        $backend_uris = (array) waRequest::param('backend_uris', []);
        if (!$backend_uris) {
            return null;
        }

        $uri_map = array_fill_keys($backend_uris, true);

        foreach ($this->getMenu() as $item) {
            // dashboard: url = '' → берём id как ключ
            $candidate = $item['url'] !== '' ? $item['url'] : $item['id'];

            if (isset($uri_map[$candidate]) && !empty($item['submenu'])) {
                return $this->markSubmenuActive($item['submenu'], $uri_map);
            }
        }

        return null;
    }

    /**
     * Ленивая загрузка структуры меню.
     * Меню из cabinetBackendMenu::getMenu() строится только один раз на экземпляр сервиса.
     */
    protected function getMenu(): array
    {
        if ($this->menu === null) {
            $this->menu = cabinetBackendMenu::getMenu();
        }

        return $this->menu;
    }

    /**
     * Подсветка активного пункта главного меню.
     */
    protected function markMainActive(array $menu): array
    {
        $backend_uris = (array) waRequest::param('backend_uris', []);
        $uri_map      = array_fill_keys($backend_uris, true);
        $has_section  = isset($backend_uris[2]); // webasyst/cabinet/<section>/...

        foreach ($menu as &$item) {
            if ($has_section) {
                // Ориентируемся по url, если он есть, иначе по id (для dashboard)
                $candidate     = $item['url'] !== '' ? $item['url'] : $item['id'];
                $item['active'] = isset($uri_map[$candidate]);
            } else {
                // Нет секции в URL → по умолчанию активен dashboard
                $item['active'] = ($item['id'] === 'dashboard');
            }
        }
        unset($item);

        return $menu;
    }

    /**
     * Подсветка активного пункта подменю по backend_uris.
     *
     * @param array $submenu
     * @param array $uri_map  ['segment' => true, ...]
     * @return array
     */
    protected function markSubmenuActive(array $submenu, array $uri_map): array
    {
        foreach ($submenu as &$item) {

            // Заголовки групп / элементы без URL никогда не бывают активными
            if (!empty($item['heading']) || empty($item['url'])) {
                $item['active'] = false;
                continue;
            }

            // Совпадение по сегменту URL.
            // /webasyst/cabinet/selling/delivering/test/
            // backend_uris = ['webasyst','cabinet','selling','delivering','test']
            // → 'delivering' подсвечивается как active.
            $item['active'] = isset($uri_map[$item['url']]);
        }
        unset($item);

        return $submenu;
    }
}
