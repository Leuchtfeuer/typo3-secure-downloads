<?php

namespace Leuchtfeuer\SecureDownloads\Tests\Functional\Domain\Repository;

use Leuchtfeuer\SecureDownloads\Domain\Model\Log;
use Leuchtfeuer\SecureDownloads\Domain\Repository\LogRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class LogRepositoryTest extends FunctionalTestCase
{

    protected array $testExtensionsToLoad = ['leuchtfeuer/secure-downloads'];

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testFindByFilterIfFilterIsEmpty(): void {

        $this->importCSVDataSet(__DIR__ . '/Fixtures/log.csv');

        /** @var LogRepository $logRepository */
        $logRepository = $this->get(LogRepository::class);


        $result = $logRepository->findByFilter(null,1,1);
        $this->assertInstanceOf(Log::class,$result[0] ?? null);
        $this->assertEquals("csm_landscape_226c4e299f" , $result[0]->getFileName());
    }

}
