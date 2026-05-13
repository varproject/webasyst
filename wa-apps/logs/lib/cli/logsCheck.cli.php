<?php

class logsCheckCli extends waCliController
{
    public function execute()
    {
        if (!logsLicensing::check()->hasPremiumLicense()) {
            return;
        }

        try {
            $users = $this->getUsers();

            if (!$users) {
                if (waSystemConfig::isDebug()) {
                    throw new Exception(_w('No users have enabled email notifications on tracked files.'));
                } else {
                    return;
                }
            }

            $this->markUpdatedTrackedFiles();
            $users_updated_files = $this->getUsersUpdatedFiles();

            if (!$users_updated_files) {
                if (waSystemConfig::isDebug()) {
                    throw new Exception(_w('No updated tracked files have been detected.'));
                } else {
                    return;
                }
            }

            $users_check_phrases = $this->getUsersCheckPhrases();
            $found_phrases_data = $this->getFoundPhrasesData($users_check_phrases, $users_updated_files);

            $view = wa()->getView();
            $view->assign('domain_url', $this->getDomainUrl());

            $subject = _w('Logs app: updated log files you have marked as tracked');
            $template = file_get_contents(wa()->getAppPath('templates/emails/cli_check_updated_files.html'));

            foreach ($users_updated_files as $user_id => $user_updated_files) {
                $user_updated_files_data = array_combine(
                    $user_updated_files,
                    array_fill(0, count($user_updated_files), '')
                );

                /** @var waUser $user */
                $user = $users[$user_id];
                $email = $user->get('email','default');
                $user_name = $user->getName();

                if (!$this->validateUserData($user, $email, $user_name)) {
                    continue;
                }

                if (!empty($users_check_phrases[$user_id])) {
                    $user_updated_files_data = $this->getUserUpdatedFilesByCheckPhrases(
                        $found_phrases_data,
                        $user_updated_files,
                        $users_check_phrases[$user_id]
                    );

                    if (!$user_updated_files_data) {
                        continue;
                    }
                }

                $updated_files_view_data = $this->getFilesViewData($user_updated_files_data);

                $view->assign('user_name', waString::escape($user_name));
                $view->assign('files', $updated_files_view_data);

                wa()->setLocale($user->getLocale());
                $body = $view->fetch('string:' . $template);

                $message = new waMailMessage($subject, $body, 'text/html');
                $message->setTo($email, $user_name);
                $message->send();
            }

            (new waAppSettingsModel())->set('logs', 'last_cli_timestamp_check', time());
        } catch (Throwable $exception) {
            waLog::log($exception->getMessage(), 'logs/cli/errors.check.log');
        }
    }

    private function getBackendUrl()
    {
        static $backend_url;

        if (is_null($backend_url)) {
            $domain_url = $this->getDomainUrl();
            $backend_url = $domain_url ? $domain_url . wa()->getConfig()->getBackendUrl() . '/' : false;
        }

        return $backend_url;
    }

    private function getDomainUrl()
    {
        static $domain_url;

        if (is_null($domain_url)) {
            try {
                wa('site');

                $site_domain = (new siteDomainModel())
                    ->select('name')
                    ->limit(1)
                    ->fetchField();
            } catch (Throwable $exception) {
                //
            }

            if (!empty($site_domain)) {
                $domain_config_path = wa('site')->getConfig()->getConfigPath('domains/' . $site_domain . '.php');

                if (is_readable($domain_config_path)) {
                    $domain_config = @include($domain_config_path);
                }

                $domain_url = (ifset($domain_config, 'ssl_all', false) ? 'https://' : 'http://') . $site_domain;
            } else {
                $domain_url = rtrim(trim(wa()->getSetting('url', '', 'webasyst')), '/');

                if ($domain_url) {
                    if (!preg_match('~^https?:\/\/~', $domain_url)) {
                        $domain_url = 'http://' . $domain_url;
                    }
                }
            }

            $domain_url = $domain_url ? $domain_url . '/' : false;
        }

        return $domain_url;
    }

    private function getUsers()
    {
        static $users;

        if (is_null($users)) {
            try {
                $users = (new waContactSettingsModel())
                    ->select('contact_id')
                    ->where('app_id = ?', $this->getAppId())
                    ->where('name = ?', 'email_updated_files')
                    ->fetchAll('contact_id');

                if (!$users) {
                     throw new Exception();
                }

                foreach ($users as $user_id => &$user) {
                    try {
                        $user = new waContact($user_id);

                        if (!$user->getRights('logs')) {
                            throw new Exception();
                        }
                    } catch (Throwable $exception) {
                        unset($users[$user_id]);
                    }
                }

                unset($user);
            } catch (Throwable $exception) {
                $users = [];
            }
        }

        return $users;
    }

    private function markUpdatedTrackedFiles()
    {
        /* @var logsConfig $config */
        $config = wa('logs')->getConfig();
        $config->markUpdatedTrackedFiles(array_keys($this->getUsers()));
    }

    private function getUsersUpdatedFiles()
    {
        $users_updated_files = (new logsTrackedModel())
            ->select('contact_id, path')
            ->where('contact_id IN (i:user_ids)', [
                'user_ids' => array_keys($this->getUsers())
            ])
            ->where('updated = ?', 1)
            ->fetchAll('contact_id', 2);

        return $users_updated_files;
    }

    private function getUsersCheckPhrases()
    {
        $users_check_phrases = (new waContactSettingsModel())
            ->select('contact_id, value')
            ->where('name = ?', 'files_check_words')
            ->where('contact_id IN (i:user_ids)', [
                'user_ids' => array_keys($this->getUsers())
            ])
            ->where('value IS NOT NULL')
            ->where('LENGTH (value) > 0')
            ->fetchAll('contact_id', true);

        array_walk($users_check_phrases, function(&$setting) {
            $setting = explode(PHP_EOL, $setting);
        });

        return $users_check_phrases;
    }

    private function getFoundPhrasesData($users_check_phrases, $users_updated_files)
    {
        $found_phrases_files_lines = [];

        if ($users_check_phrases) {
            $all_check_phrases = array_merge(...$users_check_phrases);
            $all_file_paths = array_merge(...$users_updated_files);
            $found_phrases_files_lines = logsHelper::getFilesByText($all_check_phrases, $all_file_paths);
        }

        return $found_phrases_files_lines;
    }

    private function validateUserData(waContact $user, &$email, $name)
    {
        $email = $user->get('email', 'default');

        if (!$email) {
            logsHelper::log(sprintf_wp(
                'User %s has no email address to send a notification on updated log files.',
                $name
            ));

            return false;
        }

        static $email_validator;

        if (!$email_validator) {
            $email_validator = new waEmailValidator();
        }

        if (!$email_validator->isValid($email)) {
            $valid_email = false;
            $user_emails = (array) $user->get('email', 'value');

            foreach ($user_emails as $user_email) {
                if ($user_email == $email) {
                    continue;
                }

                if ($email_validator->isValid($user_email)) {
                    $email = $user_email;
                    $valid_email = true;
                    break;
                }
            }

            if (!$valid_email) {
                logsHelper::log(sprintf_wp(
                    'User %s has no invalid email address to send a notification on updated log files: %s.',
                    $name,
                    $email
                ));

                return false;
            }
        }

        return true;
    }

    private function getUserUpdatedFilesByCheckPhrases($data, $user_files, $user_phrases)
    {
        $result = [];

        foreach ($data as $check_phrase => $files_lines) {
            if (!in_array($check_phrase, $user_phrases)) {
                continue;
            }

            foreach ($files_lines as $file_path => $line) {
                if (!in_array($file_path, $user_files)) {
                    continue;
                }

                // show the occurrence of the first found word within a file
                if (isset($result[$file_path])) {
                    continue;
                }

                $result[$file_path] = $line;
            }
        }

        return $result;
    }

    private function getFilesViewData($files_data)
    {
        $result = [];
        $backend_url = $this->getBackendUrl();

        foreach ($files_data as $path => $file_text) {
            $result[] = [
                'path' => $path,
                'url' => $backend_url ? $this->getBackendUrl() . 'logs/'
                        . '?'
                        . http_build_query([
                            'action' => 'file',
                            'path' => $path,
                        ])
                    : null,
                'text' => $file_text,
            ];
        }

        return $result;
    }
}
