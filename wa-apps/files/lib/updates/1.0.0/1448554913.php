<?php

$m = new waModel();
$m->exec("
  REPLACE INTO wa_contact_rights (group_id, app_id, name, value)
  SELECT group_id, 'files', '" . filesRightConfig::RIGHT_CREATE_STORAGE  . "', 1
  FROM wa_contact_rights
  WHERE app_id = 'files' AND name = 'backend' AND value = 1
");