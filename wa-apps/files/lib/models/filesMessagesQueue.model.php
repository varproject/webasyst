<?php

class filesMessagesQueueModel extends waModel
{
    protected $table = 'files_messages_queue';

    public function sendAll()
    {
        $limit = (int) $this->getOption('send_max_count');
        for ($i = 0; $i < $limit; $i += 1) {
            $items = $this->select('*')->order('id DESC')->limit(1)->fetchAll();
            if (!$items) {       // no items anymore
                break;
            }
            $item = $items[0];
            $this->deleteById($item['id']);

            if (!$item['data']) {
                continue;
            }
            $params = unserialize($item['data']);
            if (empty($params['address'])) {
                continue;
            }

            $from = ifempty($params['from'], '');
            if (!$from) {
                continue;
            }

            $subject = htmlspecialchars_decode(ifempty($params['subject'], ''));
            $content = ifset($params['content'], '');
            $content_type = 'text/html';

            foreach ($params['address'] as $email => $name) {

                // Send message
                try {

                    $m = new waMailMessage($subject, $content, $content_type);
                    $m->setTo($email, $name)->setFrom($from);

                    if (!empty($params['files']) && is_array($params['files'])) {
                        foreach ($params['files'] as $id => $f) {
                            /**
                             * @var filesSource $source
                             */
                            $source = filesSource::factory($f['source_id']);
                            $attachment_info = $source->getAttachmentInfo(array(
                                'message_queue_item' => $item,
                                'file' => $f
                            ));
                            if (empty($attachment_info) || !is_array($attachment_info)) {
                                continue;
                            }
                            if (empty($attachment_info['path'])) {
                                continue;
                            }
                            $attachment_info['name'] = ifempty($attachment_info['name'], $f['name']);
                            $m->addAttachment($attachment_info['path'], $attachment_info['name']);
                        }
                    }
                    $sent = $m->send();
                    $reason = 'waMailMessage->send() returned FALSE';
                } catch (Exception $e) {
                    $sent = false;
                    $reason = $e->getMessage();
                }
                if (!$sent) {
                    filesApp::inst()->logError(
                        sprintf(
                            "Unable to send email from %s to %s (%s): %s",
                            $from,
                            $email . ($name ? "({$name})" : ""),
                            $subject,
                            $reason
                        )
                    );
                }
            }
        }
    }

    public function pushMessage($data)
    {
        if (!empty($data['address'])) {
            if (empty($data['from'])) {
                $data['from'] = $this->getDefaultFrom();
            }
            $insert = array(
                'created' => date('Y-m-d H:i:s'),
                'data' => serialize($data)
            );
            $id = $this->insert($insert);
            $this->shrink();
            return $id;
        }
        return false;
    }

    public function clearAll()
    {
        $this->query("DELETE FROM {$this->table} WHERE 1");
    }

    public function shrink()
    {
        $max_size = (int) $this->getOption('max_size');
        $count = $this->countAll();
        if ($count > $max_size) {
            $id = $this->select('id')->order('id DESC')->limit("{$max_size}, 1")->fetchField();
            $this->query("DELETE FROM `{$this->table}` WHERE id <= {$id}");
        }
    }

    private function getOptions()
    {
        $config = filesApp::inst()->getConfig();
        return array(
            'send_max_count' => $config->getMessagesQueueSendMaxCount(),
            'max_size' => $config->getMessagesQueueMaxSize()
        );
    }

    private function getOption($name)
    {
        $options = $this->getOptions();
        return isset($options[$name]) ? $options[$name] : null;
    }

    /**
     * Parse simple HTML $template_string, replace $vars and push into queue to send to $address.
     *
     * Special {SEPARATOR} var is used to divide 'From', 'Subject' and 'Body' parts of a template.
     * Newlines are replaced with <br>s unless at least one <br> is found in body text.
     *
     * Example:
     *
     * Sample email subject {SOME_VAR}
     * {SEPARATOR}
     * Sample email Body. Allows to use <u>HTML</u>.
     *
     * {SOME_VAR}
     *
     * --
     * Best regards, WebAsyst support team.
     *
     * @param string $address
     * @param string $template_string
     * @param array $vars key => value pairs to replace in template. By consideration, keys should be in "{SOMETHING}" form.
     * @param int $log_id
     * @return bool
     * @throws waException if template does not contain two {SEPARATOR}s
     */
    public function pushTemplate($address, $template_id, $vars, $files = null)
    {
        if (!$address) {
            return false;
        }
        if (empty($vars['{LOCALE}'])) {
            $vars['{LOCALE}'] = 'en_US';
        }
        $locale = $vars['{LOCALE}'];

        $template_path = wa()->getConfig()->getAppPath() . '/templates/messages/' . $template_id . '.' . $locale . '.html';

        if (!file_exists($template_path)) {
            return false;
        }
        $template_string = file_get_contents($template_path);

        // Load template and replace $vars
        $message = $this->substituteVars($vars, $template_string);
        $message = explode('{SEPARATOR}', $message);
        if (empty($message[1])) {
            $message[1] = ' ';
        }
        $message = array(
            'subject' => trim($message[0]),
            'content' => $message[1],
            'address' => $address,
            'from' => $this->getDefaultFrom()
        );
        if ($files) {
            $message['files'] = $files;
        }
        $this->pushMessage($message);

        return true;
    }

    /**
     * "From:" email address to use when sending email conserning request from given source.
     * Used when other methods of obtaining "From:" address (such as from source settings or email template) fail.
     */
    private function getDefaultFrom()
    {
        $from = waMail::getDefaultFrom();
        reset($from);
        $email = key($from);
        $name = current($from);
        if ($email) {
            if ($name) {
                return $name . ' <' . $email . '>';
            } else {
                return $email;
            }
        }
        return '';
    }

    private function substituteVars($vars, $message, $trim = true)
    {
        $m = str_replace(array_keys($vars), array_values($vars), $message);
        return $trim ? trim($m) : $m;
    }

}
