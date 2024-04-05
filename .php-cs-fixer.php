<?php

declare(strict_types=1);

use TYPO3\CodingStandards\CsFixerConfig;

if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

$headerComment = <<<COMMENT
This file is part of the "Secure Downloads" Extension for TYPO3 CMS.

For the full copyright and license information, please read the
LICENSE.txt file that was distributed with this source code.

(c) Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
COMMENT;

$config = CsFixerConfig::create();
$config
    ->setHeader($headerComment, true)
    ->getFinder()
    ->name('*.php')
    ->exclude('Configuration')
    ->exclude('Libraries')
    ->exclude('Resources')
    ->exclude('Migrations')
    ->notName('ext_emconf.php')
    ->notName('ext_tables.php')
    ->notName('ext_localconf.php')
    ->in(dirname(__DIR__));

return $config;
