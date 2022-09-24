<?php

use Symfony\Component\Mailer\Bridge\Sendgrid\Transport\SendgridSmtpTransport;
use Symfony\Component\Mailer\Transport;

require 'vendor/autoload.php';

$dsn = 'sendgrid+smtp://apikey:SG.redacted@smtp.sendgrid.com:587';
$transport = Transport::fromDsn($dsn);
assert($transport instanceof SendgridSmtpTransport);

var_dump($transport->getStream()->getPort());