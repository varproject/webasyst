<?php

abstract class logsBackendItemAction extends logsViewAction
{
    protected $action;
    protected $id;
    protected $value;

    public function __construct()
    {
        parent::__construct();
        $this->value = waRequest::get($this->id, '');
    }

    public function execute()
    {
        try {
            if (!strlen(strval($this->value))) {
                logsHelper::redirect();
            }

            if (!$this->check()) {
                logsHelper::redirect();
            }

            $page = waRequest::get('page', null, waRequest::TYPE_INT);

            if (!empty($page) && $page < 1) {
                $this->redirect(logsHelper::getParamsRemovedUrl(['page']));
            }

            $item = $this->getItem([
                'page' => $page,
            ]);

            if (isset($item['error']) && strlen(strval(ifset($item, 'error', '')))) {
                throw new Exception($item['error']);
            }

            if ($page > 1 && $page >= $item['page_count']) {
                $this->redirect(logsHelper::getParamsRemovedUrl(['page']));
            }

            if ($this->action == 'file') {
                if (is_string($query = logsHelper::getFileContentsSearchQuery())) {
                    $found_pages = $item['search']['pages'] ?? null;

                    if ($found_pages) {
                        $last_found_page = max($found_pages);

                        if (logsLicensing::check()->hasPremiumLicense()) {
                            $redirect_to_last_found_page = $page && !in_array($page, $found_pages)
                                || !$page && $last_found_page < $item['page_count'];

                            if ($redirect_to_last_found_page) {
                                $this->redirect('?' . http_build_query($this->getItemUrlParams() + [
                                    'page' => $last_found_page,
                                    'query' => $query,
                                ]));
                            }
                        } else {
                            if ($page && $page < $item['page_count']) {
                                $this->redirect($this->getItemUrl());
                            }

                            if (
                                $item['page_count'] > 1
                                && $last_found_page < $item['page_count']
                            ) {
                                $this->view->assign('premium_promo_feature', 'search-file-contents-pages');

                                throw new Exception(
                                    _w('Your query wasn’t found on the last page.')
                                        . ' '
                                        . _w('<strong>Searching on other file pages</strong> is available with the <em>Logs+</em> license.')
                                        . ' '
                                        . '<a href="javascript: void(0);" class="view-premium-link nowrap" data-feature="search-file-contents">'
                                        . _w('Read more') . ' ›'
                                        . '</a>'
                                );
                            }
                        }

                        $this->view->assign('highlighting_pattern', logsHelper::getQueryHighlightingPattern($query));
                        $this->view->assign('highlighting_replacement', logsHelper::getQueryHighlightingReplacement());
                    } else {
                        $this->view->assign('dialog_header', _w('Search results'));
                        $this->view->assign('no_error', true);
                        throw new Exception(sprintf_wp(
                            'Text <mark %s>%s</mark> not found.',
                            'style="white-space: pre-wrap;"',
                            waString::escape($query)
                        ));
                    }
                } else {
                    // if only whitespaces are passed as search query in direct URL
                    if (strlen(waRequest::get('query', ''))) {
                        $this->redirect($this->getItemUrl());
                    }
                }
            }

            $this->view->assign('item', $item);
        } catch (Exception $e) {
            $this->view->assign('error', $e->getMessage());
        } finally {
            $this->view->assign('redirect_url', $this->getItemUrl());
        }

        $this->view->assign('item_lines_url', '?action=itemLines');
        $this->setTemplate('ItemView.html', true);
    }

    protected function getItemUrlParams()
    {
        return [
            'action' => $this->action,
            $this->id => $this->value,
        ];
    }

    protected function getItemUrl()
    {
        static $url;

        if (!$url) {
            $url = '?' . http_build_query($this->getItemUrlParams());
        }

        return $url;
    }

    abstract protected function check();
    abstract protected function getItem($params);
}
