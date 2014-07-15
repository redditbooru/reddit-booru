<?php

namespace Controller {

    use OAuth2;
    use Lib;
    use Api;

    class UAS extends BasePage {

        public static function render() {

            session_start();

            parent::render();

            $action = Lib\Url::Get('action', null);

            switch ($action) {
                case 'login':
                    // Redirect to the reddit OAuth2 login endpoint
                    header('Location: ' . Api\User::getLoginUrl());
                    exit;
                case 'authenticate':
                    $code = Lib\Url::Get('code');
                    if (false !== $code) {
                        Api\User::authenticateUser($code);
                        header('Location: /');
                        exit;
                    }
                    break;
                case 'vote':
                    $user = Api\User::getCurrentUser();
                    if ($user) {
                        $user->vote(Lib\Url::Get('id'), Lib\Url::GetInt('dir'));
                    }
                    exit;
                    break;
            }

            session_write_close();

        }

    }

}