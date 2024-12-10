<?php

/*
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\SecureDownloads\Tests\Functional\Domain\Repository;

use Leuchtfeuer\SecureDownloads\Domain\Model\Log;
use Leuchtfeuer\SecureDownloads\Domain\Repository\LogRepository;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Filter;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\DefaultToken;
use PHPUnit\Framework\Attributes\CoversClass;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(LogRepository::class)]
class LogRepositoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['leuchtfeuer/secure-downloads'];
    private LogRepository $logRepository;

    protected array $pathsToLinkInTestInstance = [
        'typo3conf/ext/secure_downloads/Tests/Functional/Domain/Repository/Fixtures/Folders/assets' => 'fileadmin/assets',
    ];

    public function setUp(): void
    {
        parent::setUp();

        /** @var LogRepository $logRepository */
        $this->logRepository = $this->get(LogRepository::class);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/log.csv');
    }

    public function testFindByFilterIfFilterIsEmpty(): void
    {
        $result = $this->logRepository->findByFilter(null, 1, PHP_INT_MAX);
        self::assertInstanceOf(Log::class, $result[0] ?? null);
        self::assertEquals('fixture_7', $result[0]->getFileName());
        self::assertCount(7, $result);
    }

    public function testFindByFilterIfFilterIsEmptyWithPageOffset(): void
    {
        $result = $this->logRepository->findByFilter(null, 2, 2);
        self::assertEquals('fixture_5', $result[0]->getFileName());
        self::assertEquals('fixture_4', $result[1]->getFileName());
        $result = $this->logRepository->findByFilter(null, 4, 2);
        self::assertEquals('fixture_1', $result[0]->getFileName());
        self::assertCount(1, $result);
    }

    public function testFindByFilterIfFilterIsEmptyWithWrongCurrentPageOrOffset(): void
    {
        $result = $this->logRepository->findByFilter(null, 5, 3);
        self::assertCount(0, $result);
        $result = $this->logRepository->findByFilter(null, -1, 3);
        self::assertCount(0, $result);
        $result = $this->logRepository->findByFilter(null, 1, -1);
        self::assertCount(0, $result);
    }

    public function testFindByFilterIfFilterMediaTypeIsJPG(): void
    {
        $filter = new Filter();
        $filter->setFileType('image/jpeg');
        $result = $this->logRepository->findByFilter($filter, 1, 7);
        self::assertCount(3, $result);
    }

    public function testFindByFilterIfFilterForLoggedInUser(): void
    {
        $filter = new Filter();
        $filter->setUserType(Filter::USER_TYPE_LOGGED_ON);
        $result = $this->logRepository->findByFilter($filter, 1, 7);
        self::assertCount(4, $result);
    }

    public function testFindByFilterIfFilterForNotLoggedInUser(): void
    {
        $filter = new Filter();
        $filter->setUserType(Filter::USER_TYPE_LOGGED_OFF);
        $result = $this->logRepository->findByFilter($filter, 1, 7);
        self::assertCount(3, $result);
    }

    public function testFindByFilterIfFilterForPeriodFrom(): void
    {
        $filter = new Filter();
        $filter->setFrom('Wed Oct 02 2024 09:06:17 GMT+0000');
        $result = $this->logRepository->findByFilter($filter, 1, 7);
        self::assertCount(4, $result);
    }

    public function testFindByFilterIfFilterForPeriodTill(): void
    {
        $filter = new Filter();
        $filter->setTill('Wed Oct 02 2024 09:06:17 GMT+0000');
        $result = $this->logRepository->findByFilter($filter, 1, 7);
        self::assertCount(4, $result);
    }

    public function testFindByFilterIfFilterForPeriodFromTill(): void
    {
        $filter = new Filter();
        $filter->setFrom('Wed Oct 02 2024 09:06:17 GMT+0000');
        $filter->setTill('Wed Oct 02 2024 09:06:17 GMT+0000');
        $result = $this->logRepository->findByFilter($filter, 1, 7);
        self::assertCount(1, $result);
    }

    public function testFindByFilterIfFilterForFeUserId(): void
    {
        $filter = new Filter();
        $filter->setFeUserId(5);
        $result = $this->logRepository->findByFilter($filter, 1, 7);
        self::assertCount(1, $result);
        self::assertEquals('fixture_6', $result[0]->getFileName());
    }

    public function testFindByFilterIfFilterForPageId(): void
    {
        $filter = new Filter();
        $filter->setPageId(6);
        $result = $this->logRepository->findByFilter($filter, 1, 7);
        self::assertCount(2, $result);
        self::assertEquals(2, $this->logRepository->countByFilter($filter));
        self::assertEquals('fixture_3', $result[0]->getFileName());
    }

    public function testCountByFilter(): void
    {
        $filter = new Filter();
        $filter->setPageId(6);
        self::assertEquals(2, $this->logRepository->countByFilter($filter));
    }

    public function testCountByFilterWithEmptyFilter(): void
    {
        $result = $this->logRepository->countByFilter(null);
        self::assertEquals(7, $result);
    }

    public function testGetFirstTimestampByFilterWithEmptyFilter(): void
    {
        $result = $this->logRepository->getFirstTimestampByFilter(null);
        self::assertEquals(1727859973, $result);
    }

    public function testGetFirstTimestampByFilterWithEmptyFilterAndReverse(): void
    {
        $result = $this->logRepository->getFirstTimestampByFilter(null, true);
        self::assertEquals(1727860178, $result);
    }

    public function testGetFirstTimestampByFilter(): void
    {
        $filter = new Filter();
        $filter->setFrom('Wed Oct 02 2024 09:06:17 GMT+0000');
        $result = $this->logRepository->getFirstTimestampByFilter($filter);
        self::assertEquals(1727859977, $result);
    }

    public function testGetTrafficSumByFilterWithEmptyFilter(): void
    {
        $result = $this->logRepository->getTrafficSumByFilter(null);
        self::assertEquals(296793.0, $result);
    }

    public function testGetTrafficSumByFilter(): void
    {
        $filter = new Filter();
        $filter->setFrom('Wed Oct 02 2024 09:06:17 GMT+0000');
        $filter->setTill('Wed Oct 02 2024 09:06:17 GMT+0000');
        $result = $this->logRepository->getTrafficSumByFilter($filter);
        self::assertEquals(67008.0, $result);
    }

    public function testLogDownload(): void
    {
        $token = new DefaultToken();
        $token->setFile('fileadmin/assets/red.png');
        $this->logRepository->logDownload($token, 2, 'image/png', 1);
        $result = $this->logRepository->countByFilter(null);
        self::assertEquals(8, $result);
    }
}
