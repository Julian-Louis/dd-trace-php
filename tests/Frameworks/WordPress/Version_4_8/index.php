<?php
/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * @package WordPress
 */

# Appsec mock. This wont be needed on customer apps since this functions will be exposed by appsec.
require __DIR__.'/../../../Appsec/Mock.php';

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
define('WP_USE_THEMES', true);

/** Loads the WordPress Environment and Template */
require(dirname(__FILE__) . '/wp-blog-header.php');
