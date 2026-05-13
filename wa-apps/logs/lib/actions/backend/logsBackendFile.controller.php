<?php

class logsBackendFileController extends logsBackendItemController
{
    protected function check()
    {
        logsItemFile::check(waRequest::get('path', ''));
    }

    protected function getData()
    {
        if (waRequest::isXMLHttpRequest()) {
            $path = waRequest::get('path', '');

            $file_item = new logsItemFile($path);
            $file = $file_item->get([
                'first_line' => waRequest::post('first_line', 0, waRequest::TYPE_INT),
                'last_line' => waRequest::post('last_line', 0, waRequest::TYPE_INT),
                'direction' => waRequest::post('direction', '', waRequest::TYPE_STRING_TRIM),
                'last_eol' => waRequest::post('last_eol', '', waRequest::TYPE_STRING_TRIM),
                'file_end_eol' => waRequest::post('file_end_eol', '', waRequest::TYPE_STRING_TRIM),
                'check' => false,
            ]);

            $response = $file;
            unset($response['error']);
            $this->json_response['data'] = $response;

            if ($file['error']) {
                throw new Exception($file['error']);
            }

            $query = logsHelper::getFileContentsSearchQuery();

            $item_lines_action_params = [
                'html' => $file['contents'],
                'highlighting_pattern' => is_string($query) ? logsHelper::getQueryHighlightingPattern($query) : null
            ];

            $this->json_response['data']['contents'] = (new logsItemLinesAction($item_lines_action_params))->display(false);
            $this->json_response['data']['last_eol'] = $file['last_eol'];
            $this->json_response['data']['file_end_eol'] = $file['file_end_eol'];
            $this->json_response['data']['file_size'] = logsHelper::formatSize(filesize(logsHelper::getFullPath($path)));

            $this->markTrackedFileAsNotUpdated($path);
        } else {
            $this->executeAction(new logsBackendFileAction());
        }
    }

    private function markTrackedFileAsNotUpdated($path)
    {
        (new logsTrackedModel())->updateByField([
            'path' => $path,
            'contact_id' => $this->getUserId(),
        ], [
            'viewed_datetime' => date('Y-m-d H:i:s'),
            'updated' => 0,
        ]);
    }
}
