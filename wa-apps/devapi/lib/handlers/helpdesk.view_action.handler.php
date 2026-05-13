<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiHelpdeskView_actionHandler extends waEventHandler
{
    public function execute(&$params)
    {
        if (
            version_compare(wa()->getVersion('helpdesk'), '2.0') < 0 ||
            !wa()->getUser()->getRights('devapi') ||
            wa()->whichUI('helpdesk') === '1.3' ||
            !$params['action'] instanceof helpdeskRequestsInfoAction ||
            !(new waAppSettingsModel())->get('devapi', 'helpdesk')
        ) return false;
        $client_tags = [];
        $apps = wa()->getApps();
        $client_id = $params['action']->client['id'];
        $model = new waModel();
        if (isset($apps['crm']) && $client_id) {
            $query = <<<SQL
select t.name from crm_tag t join crm_contact_tags ct on t.id=ct.tag_id
where ct.contact_id=i:contact_id
SQL;
            $client_tags = $model->query($query, ['contact_id' => $client_id])->fetchAll();
        }
        $tags = '<ul class="chips small" style="margin: 0; line-height: unset">';
        foreach ($client_tags as $t) {
            $tags .= sprintf('<li class="tag selected" title="Тег CRM"><a style="cursor: default"># %s</a></li>', $t['name']);
        }
        $tags .= '</ul>';
        if ($client_id) {
            $query = <<<SQL
select count(*) cnt from helpdesk_request where client_contact_id=i:client_id and id!=i:id
SQL;
            $totalRequests = $model->query($query, ['client_id' => $client_id, 'id' => $params['action']->request->id])->fetchField('cnt');
        }
        else $totalRequests = 1;
        $appUtils = isset($apps['utils']) && $apps['utils']['vendor'] === '834834' && $client_id;
        $view = wa()->getView();
        $view->assign([
            'tags' => $tags,
            'appUtils' => $appUtils,
            'client_id' => $client_id,
            'totalRequests' => $totalRequests
        ]);
        $template = wa()->getAppPath('templates/handlers/helpdesk/viewAction.html', 'devapi');
        $html = $view->fetch($template);
        return $html;
    }
}