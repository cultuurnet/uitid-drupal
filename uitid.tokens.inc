<?php

/**
 * @file
 * Tokens for UiTiD module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function uitid_token_info() {
  $info = [];

  $info['types']['uitiduser'] = [
    'name' => t('UiTID user'),
    'description' => t('Custom tokens with values from the active UiTID user.'),
  ];

  $info['tokens']['uitiduser']['name'] = [
    'name' => t('UiTID user name'),
    'description' => t('The name of the active UiTID user.'),
  ];

  $info['tokens']['uitiduser']['first_name'] = [
    'name' => t("UiTID user's first name"),
    'description' => t('The first name of the active UiTID user.'),
  ];

  $info['tokens']['uitiduser']['email'] = [
    'name' => t('UiTID user email'),
    'description' => t('The email address of the active UiTID user.'),
  ];

  $info['tokens']['uitiduser']['street'] = [
    'name' => t('UiTID user street'),
    'description' => t('The street (and number) of the active UiTID user.'),
  ];

  $info['tokens']['uitiduser']['zipcode'] = [
    'name' => t('UiTID user zipcode'),
    'description' => t('The zipcode of the active UiTID user.'),
  ];

  $info['tokens']['uitiduser']['city'] = [
    'name' => t('UiTID user city'),
    'description' => t('The city of the active UiTID user.'),
  ];

  $info['tokens']['uitiduser']['country'] = [
    'name' => t('UiTID user country'),
    'description' => t('The country of the active UiTID user.'),
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function uitid_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type === 'uitiduser') {
    /** @var \Drupal\uitid\UitIdCurrentUserInterface $currentUser */
    $currentUser = \Drupal::service('uitid.current_user');

    try {
      if ($currentUser->isUitIdUser()) {
        $userInfo = $currentUser->getUser();

        foreach ($tokens as $name => $original) {
          switch ($name) {
            case 'name':
              $replacements[$original] = $userInfo['name'] ?? '';
              break;

            case 'first_name':
              $replacements[$original] = $userInfo['https://publiq.be/first_name'] ?? '';
              break;

            case 'email':
              $replacements[$original] = $userInfo['email'] ?? '';
              break;

            // @todo: Add missing token values
            case 'street':
              $replacements[$original] = $userInfo[''];
              break;

            case 'zipcode':
              $replacements[$original] = $userInfo[''];
              break;

            case 'city':
              $replacements[$original] = $userInfo[''];
              break;

            case 'country':
              $replacements[$original] = $userInfo[''];
              break;
          }
        }
      }
      else {
        foreach ($tokens as $original) {
          $replacements[$original] = '';
        }
      }
    }
    catch (\Exception $e) {
      foreach ($tokens as $original) {
        $replacements[$original] = '';
      }
    }
  }

  return $replacements;
}
