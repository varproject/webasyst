<?php

interface shopBtobPluginSalesChannelTypeInterface
{
    // Получить нужные переменные, для формы текущей вкладки и передать в шаблон
    public function renderForm(waSmarty3View $view, array $channel, array $settings): void;

    // Валидация и вывод ошибок полей
    public function validateParams(?int $id, array &$params, string $params_mode): array;
}
