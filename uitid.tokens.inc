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

  $info['tokens']['uitiduser']['first_name'] = [
    'name' => t("UiTID user's first name"),
    'description' => t('The first name of the active UiTID user.'),
  ];

  $info['tokens']['uitiduser']['email'] = [
    'name' => t('UiTID user email'),
    'description' => t('The email address of the active UiTID user.'),
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
            case 'first_name':
              $replacements[$original] = $userInfo['https://publiq.be/first_name'] ?? '';
              break;

            case 'email':
              $replacements[$original] = $userInfo['email'] ?? '';
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
