<?php

return array(
    'files_file' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'sid' => array('varchar', 32),
        'parent_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'storage_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'name' => array('varchar', 255, 'null' => 0),
        'left_key' => array('int', 11),
        'right_key' => array('int', 11),
        'depth' => array('int', 11),
        'contact_id' => array('int', 11),
        'size' => array('bigint', 20, 'null' => 0, 'default' => '0'),
        'md5_checksum' => array('varchar', 32),
        'count' => array('int', 11, 'null' => 0, 'default' => '0'),
        'ext' => array('varchar', 10),
        'type' => array('enum', "'file','folder'", 'null' => 0, 'default' => 'file'),
        'create_datetime' => array('datetime'),
        'update_datetime' => array('datetime'),
        'hash' => array('varchar', 32),
        'source_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'source_inner_id' => array('varbinary', 255),
        'source_path' => array('varchar', 255),
        'mark' => array('varchar', 50),
        'in_copy_process' => array('bigint', 20, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'parent_id' => 'parent_id',
            'storage_id' => 'storage_id',
            'contact_id' => 'contact_id',
            'ns_keys' => array('left_key', 'right_key'),
            'source_id_path' => array('source_id', 'source_path', 'unique' => 1),
            'source_id_inner_id' => array('source_id', 'source_inner_id', 'unique' => 1),
            'in_copy_process' => 'in_copy_process'
        )
    ),
    'files_storage' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'create_datetime' => array('datetime'),
        'contact_id' => array('int', 11),
        'access_type' => array('enum', "'personal','limited','everyone'"),
        'name' => array('varchar', 255),
        'source_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'count' => array('int', 11, 'null' => 0, 'default' => '0'),
        'icon' => array('varchar', 255, 'null' => 1),
        ':keys' => array(
            'PRIMARY' => 'id',
            'contact_id' => 'contact_id'
        )
    ),
    'files_file_rights' => array(
        'file_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'group_id' => array('int', 11, 'null' => 0),
        'level' => array('int', 11, 'null' => 0),
        'root_file_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'create_datetime' => array('datetime'),
        'creator_contact_id' => array('int', 11),
        ':keys' => array(
            'PRIMARY' => array('file_id', 'group_id'),
            'root_file_id' => 'root_file_id'
        )
    ),
    'files_source' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'type' => array('varchar', 50),
        'name' => array('varchar', 255),
        'contact_id' => array('int', 11),
        'create_datetime' => array('datetime'),
        'storage_id' => array('int', 11),
        'folder_id' => array('int', 11),
        'synchronize_datetime' => array('datetime'),
        'in_progress_datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => 'id'
        )
    ),
    'files_source_params' => array(
        'source_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('source_id', 'name')
        )
    ),
    'files_source_sync' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'uid' => array('varchar', 32, 'null' => 0),
        'source_id' => array('int', 11, 'null' => 0),
        'source_inner_id' => array('varbinary', 255),
        'source_path' => array('varchar', 255, 'null' => 0),
        'type' => array('enum', "'file','folder','delete','move','rename'", 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'size' => array('bigint', 20, 'null' => 0, 'default' => '0'),
        'datetime' => array('datetime'),
        'slice_id' => array('varchar', 32),
        'slice_expired_datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'uid' => array('uid', 'unique' => 1),
            'source_id_path' => array('source_id', 'source_path'),
            'source_inner_id' => array('source_id', 'source_inner_id'),
            'slice_id' => 'slice_id'
        )
    ),
    'files_source_sync_params' => array(
        'source_sync_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('source_sync_id', 'name')
        )
    ),
    'files_favorite' => array(
        'contact_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'file_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => array('contact_id', 'file_id')
        )
    ),
    'files_file_comments' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'file_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'contact_id' => array('int', 11),
        'datetime' => array('datetime'),
        'content' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'file_id' => 'file_id'
        )
    ),
    'files_filter' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'create_datetime' => array('datetime'),
        'contact_id' => array('int', 11, 'null' => 0),
        'access_type' => array('enum', "'personal','shared'", 'null' => 0, 'default' => 'personal'),
        'name' => array('varchar', 255, 'null' => 0),
        'conditions' => array('text'),
        'icon' => array('varchar', 255, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'contact_id' => 'contact_id'
        )
    ),
    'files_tag' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255),
        ':keys' => array(
            'PRIMARY' => 'id',
            'name' => array('name', 'unique' => 1)
        )
    ),
    'files_file_tags' => array(
        'file_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'tag_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('file_id', 'tag_id')
        )
    ),
    'files_copytask' => array(
        'create_datetime' => array('datetime', 'null' => 0),
        'source_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'target_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'retries' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'default' => 0),
        'process_datetime' => array('datetime'),
        'offset' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'default' => 0),
        'lock' => array('varchar', 32),
        'lock_expired_datetime' => array('datetime'),
        'process_id' => array('bigint', 20, 'null' => 0, 'default' => '0'),
        'is_move' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'target_id',
            'lock' => 'lock',
            'process_id' => 'process_id',
            'source_id' => 'source_id'
        )
    ),
    'files_tasks_queue' => array(
        'id' => array('bigint', 20, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'file_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'operation' => array('enum', "'move','copy','delete'"),
        'parent_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'storage_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'parent_task_id' => array('bigint', 20, 'unsigned' => 1),
        'create_datetime' => array('datetime', 'null' => 0),
        'lock' => array('varchar', 32),
        'lock_expired_datetime' => array('datetime'),
        'process_id' => array('bigint', 20, 'null' => 0, 'default' => '0'),
        'replace' => array('tinyint', 1, 'null' => 0, 'default' => 0),
        'restore' => array('tinyint', 1, 'null' => 0, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'file_id' => 'file_id',
            'parent_task_id' => 'parent_task_id',
            'lock' => 'lock',
            'process_id' => 'process_id',
            'contact_id' => 'contact_id'
        )
    ),
    'files_messages_queue' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'created' => array('datetime'),
        'data' => array('longblob'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'created' => 'created',
        )
    ),
    'files_lock' => array(
        'token' => array('varchar', 100, 'null' => 0),
        'create_datetime' => array('datetime', 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'file_id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'default' => '0'),
        'storage_id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'default' => '0'),
        'scope' => array('enum', "'exclusive','shared'", 'null' => 0, 'default' => 'exclusive'),
        'depth' => array('enum', "'0','infinity'", 'null' => 0, 'default' => '0'),
        'timeout' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'default' => '0'),
        'expired_datetime' => array('datetime', 'null' => 0),
        'owner' => array('varchar', 100, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'token',
            'file_id' => 'file_id'
        )
    )
);
