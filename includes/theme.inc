<?php

/**
 * @file
 * Template preprocessors for culturefeed user templates.
 */

use Drupal\Core\Url;

/**
 * Preprocess a UiTiD authenticated page.
 *
 * @param array $variables
 *   Array of currently known variables.
 */
function template_preprocess_uitid_authenticated_page(array &$variables) {
  $variables['connect_url'] = Url::fromRoute('uitid.connect', [], ['query' => Drupal::destination()->getAsArray()]);
  $variables['login_message'] = t('You should be logged in to proceed.');
}
