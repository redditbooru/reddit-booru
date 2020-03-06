<?php

namespace Controller {

  use Api;
  use Lib;

  class Service implements Page {

    public static function render() {
      self::_verifySignature();

      header('Content-Type: application/json');
      $out = [];
      $action = Lib\Url::Get('action');

      switch ($action) {
        case 'start-source-update':
          $out = Api\Source::getNextSourceToUpdate();
          break;
        case 'reserve-image':
          break;
        case 'create-post':
          break;
        case 'update-image':
          break;
        case 'update-post':
          break;
        case 'end-source-update':
          break;
      }


      echo json_encode($out);
      exit;
    }

    public static function _verifySignature() {
      $validated = false;
      $clientId = Lib\Url::Get('HTTP_X_CLIENT_ID', null, $_SERVER);
      $signature = Lib\Url::GET('HTTP_X_SIGNATURE', null, $_SERVER);

      if ($clientId === SERVICE_ID && $signature) {
        // Validate the signature
        unset($_GET['_q']);
        $keys = array_keys($_GET);
        sort($keys);
        foreach ($keys as $i => $key) {
          $keys[$i] = $keys[$i] . '=' . $_GET[$key];
        }

        $signedReq = hash_hmac('sha256', implode('&', $keys), SERVICE_SECRET);
        $validated = $signature === $signedReq;
      }

      if (!$validated) {
        http_response_code(403);
        exit;
      }
    }

  }

}