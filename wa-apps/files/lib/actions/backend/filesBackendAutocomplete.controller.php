<?php

class filesBackendAutocompleteController extends filesController
{
    protected $limit = 10;

    public function execute()
    {
        $data = array();
        $q = waRequest::get('term', '', waRequest::TYPE_STRING_TRIM);
        if ($q) {
            $type = preg_replace('@[^a-z]+@', '', waRequest::get('type', 'contact', waRequest::TYPE_STRING_TRIM));
            $method = $type.'Autocomplete';
            if (method_exists($this, $method)) {
                $data = $this->{$method}($q);
            } else {
                $type = null;
            }
        }
        echo json_encode($data);
        exit;
    }

    public function contactAutocomplete($q)
    {
        $m = new waModel();

        // The plan is: try queries one by one (starting with fast ones),
        // until we find 5 rows total.
        $sqls = array();

        // Name starts with requested string
        $sqls[] = "SELECT c.*
                   FROM wa_contact AS c
                   WHERE c.is_user > 0 AND c.name LIKE '".$m->escape($q, 'like')."%'
                   LIMIT {LIMIT}";

        // Email starts with requested string
        $sqls[] = "SELECT c.*, e.email
                   FROM wa_contact AS c
                       JOIN wa_contact_emails AS e
                           ON e.contact_id=c.id
                   WHERE c.is_user > 0 AND  e.email LIKE '".$m->escape($q, 'like')."%'
                   LIMIT {LIMIT}";

        // Phone contains requested string
        if (preg_match('~^[wp0-9\-\+\#\*\(\)\. ]+$~', $q)) {
            $dq = preg_replace("/[^\d]+/", '', $q);
            $sqls[] = "SELECT c.*, d.value as phone
                       FROM wa_contact AS c
                           JOIN wa_contact_data AS d
                               ON d.contact_id=c.id AND d.field='phone'
                       WHERE  c.is_user > 0 AND d.value LIKE '%".$m->escape($dq, 'like')."%'
                       LIMIT {LIMIT}";
        }

        // Name contains requested string
        $sqls[] = "SELECT c.*
                   FROM wa_contact AS c
                   WHERE c.is_user > 0 AND c.name LIKE '_%".$m->escape($q, 'like')."%'
                   LIMIT {LIMIT}";

        // Email contains requested string
        $sqls[] = "SELECT c.*, e.email
                   FROM wa_contact AS c
                       JOIN wa_contact_emails AS e
                           ON e.contact_id=c.id
                   WHERE c.is_user > 0 AND e.email LIKE '_%".$m->escape($q, 'like')."%'
                   LIMIT {LIMIT}";

        $limit = $this->limit;
        $result = array();
        $term_safe = htmlspecialchars($q);
        foreach ($sqls as $sql) {
            if (count($result) >= $limit) {
                break;
            }
            foreach ($m->query(str_replace('{LIMIT}', $limit, $sql)) as $c) {
                if (empty($result[$c['id']])) {
                    $c['name'] = waContactNameField::formatName($c);
                    $name = $this->prepare($c['name'], $term_safe);
                    $email = $this->prepare(ifset($c['email'], ''), $term_safe);
                    $phone = $this->prepare(ifset($c['phone'], ''), $term_safe);
                    $phone && $phone = '<i class="icon16 phone"></i>'.$phone;
                    $email && $email = '<i class="icon16 email"></i>'.$email;
                    $photo_url = waContact::getPhotoUrl($c['id'], $c['photo'], 20, 20, 'person');
                    $result[$c['id']] = array(
                        'id'    => $c['id'],
                        'value' => $c['id'],
                        'name'  => $c['name'],
                        'userpic20' => $photo_url,
                        'label' => '<i class="icon16 userpic20 icon userpic userpic-20 custom-mr-4" style="background-image: url('.$photo_url.');"></i>'.
                            implode(' ', array_filter(array($name, $email, $phone))),
                    );
                    if (count($result) >= $limit) {
                        break 2;
                    }
                }
            }
        }

        return array_values($result);
    }

    // Helper for contactsAutocomplete()
    protected function prepare($str, $term_safe)
    {
        return preg_replace('~('.preg_quote($term_safe, '~').')~ui', '<span class="bold highlighted">\1</span>', htmlspecialchars($str));
    }
}
