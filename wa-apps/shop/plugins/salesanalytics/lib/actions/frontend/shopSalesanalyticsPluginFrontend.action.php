<?php

class shopSalesanalyticsPluginFrontendAction extends waViewAction
{
    public function execute()
    {
        $from = waRequest::get('from', date('Y-m-01'));
        $to   = waRequest::get('to', date('Y-m-d'));

        $data = $this->getAnalytics($from, $to);

        // dd($data);
        $this->view->assign('from', $from);
        $this->view->assign('to', $to);
        $this->view->assign('data', $data);
    }

    public function getAnalytics($from = null, $to = null)
    {
        $where = [];
        $params = [];

        if ($from) {
            $where[] = 'paid_date >= s:from';
            $params['from'] = $from . ' 00:00:00';
        }

        if ($to) {
            $where[] = 'paid_date <= s:to';
            $params['to'] = $to . ' 23:59:59';
        }

        $sql_where = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $order_model = new shopOrderModel();

        $orders = $order_model->query(
            "SELECT id, contact_id, create_datetime, paid_date, total, currency
             FROM shop_order
             {$sql_where}
             ORDER BY paid_date DESC, id DESC",
            $params
        )->fetchAll();

        $total_sales = 0.0;
        $count = 0;
        $by_day = [];

        foreach ($orders as $order) {
            $count++;
            $total_sales += (float)$order['total'];

            $day = substr($order['paid_date'] ?: $order['create_datetime'], 0, 10);
            if (!isset($by_day[$day])) {
                $by_day[$day] = 0.0;
            }
            $by_day[$day] += (float)$order['total'];
        }

        ksort($by_day);

        return [
            'orders' => $orders,
            'kpi' => [
                'count' => $count,
                'total_sales' => round($total_sales, 2),
                'avg_check' => $count ? round($total_sales / $count, 2) : 0,
            ],
            'chart' => $by_day,
        ];
    }
}
