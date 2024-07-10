<?php

namespace Xima\XmKesearchRemote\Tests\Functional\Command;

use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Xima\XmKesearchRemote\Command\FetchContentCommand;

class FetchContentCommandTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/ke_search',
        'typo3conf/ext/xm_kesearch_remote',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/xm_kesearch_remote/Tests/Fixtures' => 'fileadmin/Fixtures',
    ];

    private CommandTester $commandTester;

    public function testUrlCrawler(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/tx_kesearch_indexerconfig.csv');
        $this->commandTester->execute([]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);

        $command = new FetchContentCommand($extensionConfigurationMock);
        $this->commandTester = new CommandTester($command);
    }
}
