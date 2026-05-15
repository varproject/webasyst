<?php

class shopB2bPluginBackendCustomersController extends waJsonController
{
    // Возвращает покупателей магазина для Select2.
    public function execute()
    {
        if (!wa()->getUser()->isAdmin('shop')) {
            throw new waRightsException(_w('Access denied'));
        }

        $query   = waRequest::request('q', '', waRequest::TYPE_STRING_TRIM);
        $service = new shopB2bPluginCustomerAccessService();
        $items   = [];

        foreach ($service->searchCustomers($query) as $customer) {
            $text = trim((string) ifset($customer, 'name', ''));

            if (!empty($customer['email'])) {
                $text .= ' — ' . $customer['email'];
            } elseif (!empty($customer['phone'])) {
                $text .= ' — ' . $customer['phone'];
            }

            $items[] = [
                'id'   => (int) $customer['id'],
                'text' => $text ?: '#' . $customer['id'],
            ];
        }

        $this->response = [
            'results' => $items,
        ];
    }
}
