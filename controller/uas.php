<?php

namespace Controller {

    use OAuth2;
    use Lib;

    class UAS extends BasePage {

        public static function render() {

            parent::render();

            $action = Lib\Url::Get('action', null);

            switch ($action) {
                case 'login':
                case 'authenticate':
                    break;
            }

        }

    }

}