<?php

namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;


/**
 * @property string $ip
 */
class SubscriberIP extends Model {
  public static $_table = MP_SUBSCRIBER_IPS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration
}
