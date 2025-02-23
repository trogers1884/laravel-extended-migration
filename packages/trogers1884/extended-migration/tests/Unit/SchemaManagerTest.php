<?php
namespace Trogers1884\ExtendedMigration\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Trogers1884\ExtendedMigration\Support\SchemaManager;
use InvalidArgumentException;

class SchemaManagerTest extends TestCase
{
    protected SchemaManager $manager;
    protected string $testPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new SchemaManager();
        $this->testPath = __DIR__ . '/test-migrations';

        // Create test directory if it doesn't exist
        if (!is_dir($this->testPath)) {
            mkdir($this->testPath, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (is_dir($this->testPath)) {
            rmdir($this->testPath);
        }
        parent::tearDown();
    }

    public function testCanRegisterSchema(): void
    {
        $result = $this->manager->registerSchema('test', $this->testPath);
        $this->assertTrue($result);
        $this->assertTrue($this->manager->hasSchema('test'));
    }

    public function testCannotRegisterDuplicateSchema(): void
    {
        $this->manager->registerSchema('test', $this->testPath);

        $this->expectException(InvalidArgumentException::class);
        $this->manager->registerSchema('test', $this->testPath);
    }

    public function testCanGetSchemaConfiguration(): void
    {
        $this->manager->registerSchema('test', $this->testPath, ['dep1']);

        $schema = $this->manager->getSchema('test');
        $this->assertIsArray($schema);
        $this->assertEquals('test', $schema['name']);
        $this->assertEquals($this->testPath, $schema['path']);
        $this->assertEquals(['dep1'], $schema['dependencies']);
    }

    public function testCanValidateSchema(): void
    {
        $this->manager->registerSchema('test', $this->testPath);
        $this->assertTrue($this->manager->validateSchema('test'));
    }

    public function testThrowsExceptionForInvalidDependency(): void
    {
        $this->manager->registerSchema('test', $this->testPath, ['invalid']);

        $this->expectException(InvalidArgumentException::class);
        $this->manager->validateSchema('test');
    }

    public function testCanSetSchemaPath(): void
    {
        $this->manager->registerSchema('test', $this->testPath);
        $newPath = $this->testPath . '/new';

        mkdir($newPath, 0777, true);
        $result = $this->manager->setPath('test', $newPath);

        $this->assertTrue($result);
        $this->assertEquals($newPath, $this->manager->getPath('test'));

        rmdir($newPath);
    }

    public function testCannotSetPathForNonexistentSchema(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->manager->setPath('nonexistent', $this->testPath);
    }

    public function testCanGetSchemaPath(): void
    {
        $this->manager->registerSchema('test', $this->testPath);
        $path = $this->manager->getPath('test');

        $this->assertEquals($this->testPath, $path);
    }

    public function testCannotGetPathForNonexistentSchema(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->manager->getPath('nonexistent');
    }

    public function testCanCheckIfPathExists(): void
    {
        $this->manager->registerSchema('test', $this->testPath);
        $this->assertTrue($this->manager->pathExists('test'));

        rmdir($this->testPath);
        $this->assertFalse($this->manager->pathExists('test'));
        mkdir($this->testPath, 0777, true);
    }

    public function testCannotCheckPathExistsForNonexistentSchema(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->manager->pathExists('nonexistent');
    }

    public function testCanCreatePath(): void
    {
        $this->manager->registerSchema('test', $this->testPath);
        rmdir($this->testPath);

        $result = $this->manager->createPath('test');

        $this->assertTrue($result);
        $this->assertTrue(is_dir($this->testPath));
    }

    public function testCannotCreatePathForNonexistentSchema(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->manager->createPath('nonexistent');
    }

    public function testValidatesValidPath(): void
    {
        $this->assertTrue($this->manager->validatePath($this->testPath));
    }

    public function testRejectsEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->manager->validatePath('');
    }

    public function testRejectsRelativePath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->manager->validatePath('relative/path');
    }

    public function testRejectsInvalidCharactersInPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->manager->validatePath('/path/with/invalid/char*');
    }

    public function testCanAddDependency(): void
    {
        $this->manager->registerSchema('test1', $this->testPath);
        $this->manager->registerSchema('test2', $this->testPath);

        $result = $this->manager->addDependency('test1', 'test2');

        $this->assertTrue($result);
        $this->assertContains('test2', $this->manager->getDependencies('test1'));
    }

    public function testCannotAddDependencyToNonexistentSchema(): void
    {
        $this->manager->registerSchema('test2', $this->testPath);

        $this->expectException(InvalidArgumentException::class);
        $this->manager->addDependency('nonexistent', 'test2');
    }

    public function testCannotAddNonexistentDependency(): void
    {
        $this->manager->registerSchema('test1', $this->testPath);

        $this->expectException(InvalidArgumentException::class);
        $this->manager->addDependency('test1', 'nonexistent');
    }

    public function testCannotAddSelfAsDependency(): void
    {
        $this->manager->registerSchema('test', $this->testPath);

        $this->expectException(InvalidArgumentException::class);
        $this->manager->addDependency('test', 'test');
    }

    public function testCanRemoveDependency(): void
    {
        $this->manager->registerSchema('test1', $this->testPath);
        $this->manager->registerSchema('test2', $this->testPath);
        $this->manager->addDependency('test1', 'test2');

        $result = $this->manager->removeDependency('test1', 'test2');

        $this->assertTrue($result);
        $this->assertNotContains('test2', $this->manager->getDependencies('test1'));
    }

    public function testCannotRemoveNonexistentDependency(): void
    {
        $this->manager->registerSchema('test1', $this->testPath);
        $this->manager->registerSchema('test2', $this->testPath);

        $this->expectException(InvalidArgumentException::class);
        $this->manager->removeDependency('test1', 'test2');
    }

    public function testCanValidateDependencies(): void
    {
        $this->manager->registerSchema('test1', $this->testPath);
        $this->manager->registerSchema('test2', $this->testPath);
        $this->manager->addDependency('test1', 'test2');

        $this->assertTrue($this->manager->validateDependencies('test1'));
    }

    public function testCanGetDependencyGraph(): void
    {
        $this->manager->registerSchema('test1', $this->testPath);
        $this->manager->registerSchema('test2', $this->testPath);
        $this->manager->registerSchema('test3', $this->testPath);

        $this->manager->addDependency('test1', 'test2');
        $this->manager->addDependency('test2', 'test3');

        $graph = $this->manager->getDependencyGraph();

        $this->assertIsArray($graph);
        $this->assertArrayHasKey('test1', $graph);
        $this->assertContains('test2', $graph['test1']);
        $this->assertContains('test3', $graph['test2']);
    }

    public function testDetectsCircularDependencies(): void
    {
        $this->manager->registerSchema('test1', $this->testPath);
        $this->manager->registerSchema('test2', $this->testPath);
        $this->manager->registerSchema('test3', $this->testPath);

        $this->manager->addDependency('test1', 'test2');
        $this->manager->addDependency('test2', 'test3');

        $this->expectException(InvalidArgumentException::class);
        $this->manager->addDependency('test3', 'test1');
    }

    public function testValidatesComplexDependencyChain(): void
    {
        $this->manager->registerSchema('test1', $this->testPath);
        $this->manager->registerSchema('test2', $this->testPath);
        $this->manager->registerSchema('test3', $this->testPath);
        $this->manager->registerSchema('test4', $this->testPath);

        $this->manager->addDependency('test1', 'test2');
        $this->manager->addDependency('test2', 'test3');
        $this->manager->addDependency('test3', 'test4');

        $this->assertTrue($this->manager->checkCircularDependencies('test1'));
    }
}
