<?php

class logsTrackedModel extends waModel
{
    protected $table = 'logs_tracked';
    protected $id = ['path', 'contact_id'];

    public function getFilesStatuses($paths)
    {
        if (!is_array($paths) || !$paths) {
            return [];
        }

        $tracked = (array) $this
            ->select('path, updated')
            ->where(
                'path IN(s:paths) AND contact_id = i:contact_id',
                [
                    'paths' => $paths,
                    'contact_id' => wa()->getUser()->getId(),
                ]
            )->fetchAll('path', true);


        $data = array_reduce($paths, function($result, $path) use ($tracked) {
            $result[$path] = [
                'tracked' => isset($tracked[$path]),
                'updated' => !empty($tracked[$path]),
            ];

            return $result;
        }, []);

        return $data;
    }

    public function getUpdatedFilesCount()
    {
        return $this->countByField([
            'contact_id' => wa()->getUser()->getId(),
            'updated' => 1,
        ]);
    }

    public function isTracked($path)
    {
        return (int) $this->countByField([
            'path' => $path,
            'contact_id' => wa()->getUser()->getId(),
        ]) > 0;
    }
}
