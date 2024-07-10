<?php

namespace Xima\XmKesearchRemote\Tests\Functional\Command;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Xima\XmKesearchRemote\Command\FetchContentCommand;

class FetchContentCommandTest extends FunctionalTestCase
{

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/xm_kesearch_remote',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/xm_kesearch_remote/Tests/Fixtures' => 'fileadmin/Fixtures',
    ];

    public function testFixtures(): void
    {
        $this->assertFileExists('fileadmin/Fixtures/sitemap.xml');
    }

}
