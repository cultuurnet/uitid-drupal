<?php

/**
 * @file
 * Template preprocessors for culturefeed user templates.
 */

use Drupal\Core\Link;

/**
 * Preprocess a UiTiD authenticated page.
 *
 * @param array $variables
 *   Array of currently known variables.
 */
function template_preprocess_uitid_authenticated_page(array &$variables) {
  $query = \Drupal::request()->query->all();

  $query['via'] = 'facebook';
  $variables['login_facebook'] = Link::createFromRoute(t('Login with Facebook'), 'culturefeed_user.connect', [], ['query' => $query]);

  $query['via'] = 'twitter';
  $variables['login_twitter'] = Link::createFromRoute(t('Twitter'), 'culturefeed_user.connect', [], ['query' => $query]);

  $query['via'] = 'google';
  $variables['login_google'] = Link::createFromRoute(t('Google'), 'culturefeed_user.connect', [], ['query' => $query]);

  unset($query['via']);
  $variables['login_mail'] = Link::createFromRoute(t('Email'), 'culturefeed_user.connect', [], ['query' => $query]);

  $variables['register'] = Link::createFromRoute(t('new account'), 'culturefeed_user.register', [], ['query' => $query]);

  $variables['login_message'] = t('You should be logged in to proceed.');
}
