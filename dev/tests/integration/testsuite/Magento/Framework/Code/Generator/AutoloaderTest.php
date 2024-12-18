<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Code\Generator;

use Magento\Framework\Code\Generator;
use Magento\Framework\Logger\Monolog as MagentoMonologLogger;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject as MockObject;
use Psr\Log\LoggerInterface;

/**
 * @magentoAppIsolation enabled
 */
class AutoloaderTest extends TestCase
{
    /**
     * This method exists to fix the wrong return type hint on \Magento\Framework\Api\ObjectManager::getInstance.
     * This way the IDE knows it's dealing with an instance of \Magento\TestFramework\ObjectManager and
     * not \Magento\Framework\Api\ObjectManager. The former has the method addSharedInstance, the latter does not.
     *
     * @return ObjectManager|\Magento\Framework\App\ObjectManager
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function getTestFrameworkObjectManager()
    {
        return ObjectManager::getInstance();
    }

    protected function setUp(): void
    {
        $loggerTestDouble = $this->createMock(LoggerInterface::class);
        $this->getTestFrameworkObjectManager()->addSharedInstance($loggerTestDouble, LoggerInterface::class, true);
        // magentoAppIsolation will cleanup the mess
    }

    /**
     * @param \RuntimeException $testException
     * @return Generator|MockObject
     */
    private function createExceptionThrowingGeneratorTestDouble(\RuntimeException $testException)
    {
        /** @var Generator|MockObject $generatorStub */
        $generatorStub = $this->createMock(Generator::class);
        $generatorStub->method('generateClass')->willThrowException($testException);

        return $generatorStub;
    }

    public function testLogsExceptionDuringGeneration(): void
    {
        $exceptionMessage = 'Test exception thrown during generation';
        $testException = new \RuntimeException($exceptionMessage);

        $loggerMock = ObjectManager::getInstance()->get(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('debug')->with($exceptionMessage, ['exception' => $testException]);

        $autoloader = new Autoloader($this->createExceptionThrowingGeneratorTestDouble($testException));
        $this->assertNull($autoloader->load(NonExistingClassName::class));
    }

    public function testFiltersDuplicateExceptionMessages(): void
    {
        $exceptionMessage = 'Test exception thrown during generation';
        $testException = new \RuntimeException($exceptionMessage);

        $loggerMock = ObjectManager::getInstance()->get(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('debug')->with($exceptionMessage, ['exception' => $testException]);

        $autoloader = new Autoloader($this->createExceptionThrowingGeneratorTestDouble($testException));
        $autoloader->load(OneNonExistingClassName::class);
        $autoloader->load(AnotherNonExistingClassName::class);
    }
}
