<?php

namespace Leuchtfeuer\SecureDownloads\Tests\Functional\Domain\Repository;

use Leuchtfeuer\SecureDownloads\Domain\Model\Log;
use Leuchtfeuer\SecureDownloads\Domain\Repository\LogRepository;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Filter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/** @covers \Leuchtfeuer\SecureDownloads\Domain\Repository\LogRepository */
class LogRepositoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['leuchtfeuer/secure-downloads'];
    private LogRepository  $logRepository;

    protected array $configurationToUseInTestInstance = [
       'DB' =>
           ['Connections' =>
               ['Default' =>
                   ['initCommands' => 'SET SESSION sql_mode = \'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE\';']
               ]
           ]
    ];

    public function setUp(): void
    {
        parent::setUp();

        /** @var LogRepository $logRepository */
        $this->logRepository = $this->get(LogRepository::class);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/log.csv');
    }

    public function testFindByFilterIfFilterIsEmpty(): void {
        $result = $this->logRepository->findByFilter(null,1,PHP_INT_MAX);
        $this->assertInstanceOf(Log::class,$result[0] ?? null);
        $this->assertEquals("fixture_7" , $result[0]->getFileName());
        $this->assertCount(7, $result);
    }

    public function testFindByFilterIfFilterIsEmptyWithPageOffset(): void {
        $result = $this->logRepository->findByFilter(null,2,2);
        $this->assertEquals('fixture_5',$result[0]->getFileName());
        $this->assertEquals('fixture_4',$result[1]->getFileName());
        $result = $this->logRepository->findByFilter(null,4,2);
        $this->assertEquals('fixture_1',$result[0]->getFileName());
        $this->assertCount(1, $result);
    }

    public function testFindByFilterIfFilterIsEmptyWithWrongCurrentPageOrOffset(): void {
        $result = $this->logRepository->findByFilter(null,5,3);
        $this->assertCount(0, $result);
        $result = $this->logRepository->findByFilter(null,-1,3);
        $this->assertCount(0, $result);
        $result = $this->logRepository->findByFilter(null,1,-1);
        $this->assertCount(0, $result);
    }

    public function testFindByFilterIfFilterMediaTypeIsJPG(): void {
        $filter = new Filter();
        $filter->setFileType('image/jpeg');
        $result = $this->logRepository->findByFilter($filter,1,7);
        $this->assertCount(3,$result);
    }

    public function testFindByFilterIfFilterForLoggedInUser(): void {
        $filter = new Filter();
        $filter->setUserType(Filter::USER_TYPE_LOGGED_ON);
        $result = $this->logRepository->findByFilter($filter,1,7);
        $this->assertCount(4, $result);
    }

    public function testFindByFilterIfFilterForNotLoggedInUser(): void {
        $filter = new Filter();
        $filter->setUserType(Filter::USER_TYPE_LOGGED_OFF);
        $result = $this->logRepository->findByFilter($filter,1,7);
        $this->assertCount(3, $result);
    }

    public function testFindByFilterIfFilterForPeriodFrom(): void {
        $filter = new Filter();
        $filter->setFrom('Wed Oct 02 2024 09:06:17 GMT+0000');
        $result = $this->logRepository->findByFilter($filter,1,7);
        $this->assertCount(4, $result);
    }

    public function testFindByFilterIfFilterForPeriodTill(): void {
        $filter = new Filter();
        $filter->setTill('Wed Oct 02 2024 09:06:17 GMT+0000');
        $result = $this->logRepository->findByFilter($filter,1,7);
        $this->assertCount(4, $result);
    }

    public function testFindByFilterIfFilterForPeriodFromTill(): void {
        $filter = new Filter();
        $filter->setFrom('Wed Oct 02 2024 09:06:17 GMT+0000');
        $filter->setTill('Wed Oct 02 2024 09:06:17 GMT+0000');
        $result = $this->logRepository->findByFilter($filter,1,7);
        $this->assertCount(1, $result);
    }

    public function testFindByFilterIfFilterForFeUserId(): void {
        $filter = new Filter();
        $filter->setFeUserId(5);
        $result = $this->logRepository->findByFilter($filter,1,7);
        $this->assertCount(1, $result);
        $this->assertEquals('fixture_6',$result[0]->getFileName());
    }

    public function testFindByFilterIfFilterForPageId(): void
    {
        $filter = new Filter();
        $filter->setPageId(6);
        $result = $this->logRepository->findByFilter($filter,1,7);
        $this->assertCount(2, $result);
        $this->assertEquals(2, $this->logRepository->countByFilter($filter));
        $this->assertEquals('fixture_3',$result[0]->getFileName());
    }

    public function testCountByFilter(): void
    {
        $filter = new Filter();
        $filter->setPageId(6);
        $this->assertEquals(2, $this->logRepository->countByFilter($filter));
    }

    public function testCountByFilterWithEmptyFilter ():void
    {
        $result = $this->logRepository->countByFilter(null);
        $this->assertEquals(7, $result);
    }


}
