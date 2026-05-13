<?php

class logsBackendAutocompleteFilesController extends logsBackendAutocompleteController
{
    public function execute()
    {
        if (!logsLicensing::check()->hasPremiumLicense()) {
            return [];
        }

        $query = waRequest::get('term', '');
        $items = $this->getFilesByQuery($query);
        $response = $this->getResults($items);

        $this->response($response);
    }

    private function getFilesByQuery($query)
    {
        $result = [];

        $query = trim(mb_strtolower($query));
        $all_files_paths = logsHelper::listDir(logsHelper::getLogsRootPath(), true);

        foreach ($all_files_paths as $file_path) {
            if (strlen($query ?? '') && mb_strpos($file_path, $query) !== false) {
                $value = preg_replace(
                    '~(' . wa_make_pattern($query) . ')~i',
                    '<span class="highlighted">$1</span>',
                    $file_path
                );

                $result[$file_path] = $value;
            }
        }

        return $result;
    }

    protected function getResultsContents($cut_items)
    {
        $response = [];

        foreach ($cut_items as $key => $value) {
            $response[] = [
                'value' => logsHelper::getLogsBackendUrl() . '?'
                    . http_build_query([
                        'action' => 'file',
                        'path' => $key,
                    ]),
                'label' => $value,
            ];
        }

        return $response;
    }
}
