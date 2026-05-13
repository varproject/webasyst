<?php

class filesStatistics
{
    private $order;

    /**
     * @var waModel
     */
    private $model;

    public function __construct($options = array())
    {
        $this->order = ifset($options['order']);
        $this->model = new waModel();
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }

    public function getStorages()
    {
        $order = $this->prepareOrder($this->order, array(
            'name' => 's.name',
            'count' => 's.count',
            'size' => 'size'
        ));

        $sql = "SELECT s.*, s.contact_id AS storage_contact_id, f.storage_id, SUM(f.size) AS size
                  FROM files_storage s
                  LEFT JOIN files_file f ON f.storage_id = s.id
                  WHERE f.type = s:type
                  GROUP BY s.id";

        if ($order) {
            $sql .= " ORDER BY {$order}";
        }
        $stat = $this->model->query($sql, array('type' => filesFileModel::TYPE_FILE))->fetchAll('storage_id');
        $stat = $this->workupStoragesStatistic($stat);
        return array('stat' => $stat, 'total' => $this->getTotal($stat));
    }

    public function getTrash()
    {
        $order = $this->prepareOrder($this->order, array(
            'name' => 's.name',
            'count' => 'count',
            'size' => 'size'
        ));

        $sql = "SELECT s.*, s.contact_id AS storage_contact_id, f.storage_id, SUM(f.size) AS size, COUNT(f.id) count
                  FROM files_storage s
                  LEFT JOIN files_file f ON f.storage_id = -s.id
                  WHERE f.type = s:type
                  GROUP BY s.id";
        if ($order) {
            $sql .= " ORDER BY {$order}";
        }
        $stat = $this->model->query($sql, array('type' => filesFileModel::TYPE_FILE))->fetchAll('storage_id');
        $stat = $this->workupStoragesStatistic($stat);
        return array('stat' => $stat, 'total' => $this->getTotal($stat));
    }

    public function getFileTypes()
    {
        $order = $this->prepareOrder($this->order, array(
            'ext' => 'ext',
            'count' => 'count',
            'size' => 'size'
        ));

        $sql = "SELECT ext, SUM(size) AS size, COUNT(*) AS count
                  FROM files_file
                  WHERE type = s:type AND storage_id > 0
                  GROUP BY ext";
        if ($order) {
            $sql .= " ORDER BY {$order}";
        }
        $stat = $this->model->query($sql, array('type' => filesFileModel::TYPE_FILE))->fetchAll();
        $stat = $this->workupStatistic($stat);

        return array('stat' => $stat, 'total' => $this->getTotal($stat));
    }

    public function getUsers()
    {
        $order = $this->prepareOrder($this->order, array(
            'name' => 'c.name',
            'count' => 'count',
            'size' => 'size'
        ));

        $sql = "SELECT c.id, c.name, c.firstname, c.middlename, c.lastname, c.is_company, c.company, c.photo, SUM(f.size) AS size, COUNT(*) AS count
                FROM files_file f
                LEFT JOIN wa_contact c ON c.id = f.contact_id
                WHERE type = s:type AND f.storage_id > 0
                GROUP BY f.contact_id";
        if ($order) {
            $sql .= " ORDER BY {$order}";
        }

        $stat = $this->model->query($sql, array('type' => filesFileModel::TYPE_FILE))->fetchAll();

        foreach ($stat as &$item) {
            $item['name'] = waContactNameField::formatName($item);
            $item['photo_url_20'] = waContact::getPhotoUrl($item['id'], $item['photo'], 20);
        }
        unset($item);

        if ($order === 'c.name') {
            usort($stat, wa_lambda('$a, $b', 'strcmp($a["name"], $b["name"])'));
        }

        $stat = $this->workupStatistic($stat);
        return array('stat' => $stat, 'total' => $this->getTotal($stat));
    }

    private function workupStatistic($stat)
    {
        foreach ($stat as &$item) {
            $item['size_str'] = filesApp::formatFileSize(ifset($item['size'], 0));
        }
        unset($item);
        return $stat;
    }

    private function workupStoragesStatistic($stat)
    {
        $sm = new filesStorageModel();
        $stat = $sm->workupStorages($stat);
        $stat = $this->workupStatistic($stat);

        // extract contact stat for personal storages
        $contact_ids = filesApp::getFieldValues($stat, 'storage_contact_id');
        if ($contact_ids) {
            $col = new waContactsCollection('id/' . join(',', $contact_ids));
            $empty_contact = array(
                'id' => '',
                'name' => '',
                'photo_url_20' => waContact::getPhotoUrl(0, 0, 20)
            );
            $contacts = array();
            foreach ($col->getContacts('*,photo_url_20') as $contact) {
                $contact['name'] = waContactNameField::formatName($contact);
                $contacts[$contact['id']] = $contact;
            }
            foreach ($stat as &$item) {
                $contact = ifset($contacts[$item['storage_contact_id']], $empty_contact);
                $item['storage_contact'] = array(
                    'id' => $item['storage_contact_id'],
                    'name' => $contact['name'] ? $contact['name'] : (_w('Deleted contact') . ' #' . $item['storage_contact_id']),
                    'photo_url_20' => $contact['photo_url_20']
                );
            }
            unset($item);
        }
        return $stat;
    }

    private function getTotal($stat)
    {
        $total = array(
            'size' => 0,
            'count' => 0
        );
        foreach ($stat as $item) {
            $total['size'] += ifset($item['size'], 0);
            $total['count'] += ifset($item['count'], 0);
        }
        $total['size_str'] = filesApp::formatFileSize($total['size']);
        return $total;
    }

    private function prepareOrder($order, $order_map = array())
    {
        if (!$order_map) {
            return '';
        }
        $order_ar = array_map('trim', explode(' ', $order));
        $order_map_values = array_values($order_map);
        $order_ar[0] = ifset($order_map[$order_ar[0]], $order_map_values[0]);
        $order_ar[1] = ifset($order_ar[1], 'asc');
        return join(' ', $order_ar);
    }

}