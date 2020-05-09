<?php

namespace {
    die('Access denied');
}

namespace Bitmotion\SecureDownloads\Cache {

    abstract class AbstractCache extends \Leuchtfeuer\SecureDownloads\Cache\AbstractCache
    {
    }

    class DecodeCache extends \Leuchtfeuer\SecureDownloads\Cache\DecodeCache
    {
    }

    class EncodeCache extends \Leuchtfeuer\SecureDownloads\Cache\EncodeCache
    {
    }
}

namespace Bitmotion\SecureDownloads\Controller {

    class LogController extends \Leuchtfeuer\SecureDownloads\Controller\LogController
    {
    }
}

namespace Bitmotion\SecureDownloads\Domain\Model {

    class Log extends \Bitmotion\Auth0\Domain\Model\Auth0\Management\Log
    {
    }
}

namespace Bitmotion\SecureDownloads\Domain\Repository {

    class LogRepository extends \Leuchtfeuer\SecureDownloads\Domain\Repository\LogRepository
    {
    }
}

namespace Bitmotion\SecureDownloads\Domain\Transfer {

    class ExtensionConfiguration extends \Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration
    {
    }

    class Filter extends \Leuchtfeuer\SecureDownloads\Domain\Transfer\Filter
    {
    }

    class Statistic extends \Leuchtfeuer\SecureDownloads\Domain\Transfer\Statistic
    {
    }
}

namespace Bitmotion\SecureDownloads\EventListener {

    class SecureDownloadsEventListener extends \Leuchtfeuer\SecureDownloads\EventListener\SecureDownloadsEventListener
    {
    }
}

namespace Bitmotion\SecureDownloads\Factory {

    class SecureLinkFactory extends \Leuchtfeuer\SecureDownloads\Factory\SecureLinkFactory
    {
    }
}

namespace Bitmotion\SecureDownloads\Middleware {

    class FileDeliveryMiddleware extends \Leuchtfeuer\SecureDownloads\Middleware\FileDeliveryMiddleware
    {
    }
}

namespace Bitmotion\SecureDownloads\Resource {

    class FileDelivery extends \Leuchtfeuer\SecureDownloads\Resource\FileDelivery
    {
    }
}

namespace Bitmotion\SecureDownloads\Service {

    class SecureDownloadService extends \Leuchtfeuer\SecureDownloads\Service\SecureDownloadService
    {
    }
}

namespace Bitmotion\SecureDownloads\UserFunctions {

    class CheckConfiguration extends \Leuchtfeuer\SecureDownloads\UserFunctions\CheckConfiguration
    {
    }
}
