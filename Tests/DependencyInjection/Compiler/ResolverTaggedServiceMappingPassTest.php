<?php

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\DependencyInjection\Compiler\ResolverTaggedServiceMappingPass;
use Overblog\GraphQLBundle\Resolver\ResolverResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ResolverTaggedServiceMappingPassTest extends TestCase
{
    /** @var ContainerBuilder */
    private $container;

    public function setUp()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('injected_service', new Definition(FakeInjectedService::class));

        $container->register('overblog_graphql.resolver_resolver', ResolverResolver::class);

        $this->container = $container;
    }

    private function addCompilerPassesAndCompile()
    {
        $this->container->addCompilerPass(new ResolverTaggedServiceMappingPass());
        $this->container->addCompilerPass(new FakeCompilerPass());
        $this->container->compile();
    }

    public function testCompilationWorksPassConfigDirective()
    {
        $testResolver = new Definition(ResolverTestService::class);
        $testResolver
            ->addTag('overblog_graphql.resolver', [
                'alias' => 'test_resolver', 'method' => 'doSomethingWithContainer',
            ]);

        $this->container->setDefinition('test_resolver', $testResolver);

        $this->addCompilerPassesAndCompile();

        $this->assertTrue($this->container->has('test_resolver'));
    }

    public function testTagAliasIsValid()
    {
        $testResolver = new Definition(ResolverTestService::class);
        $testResolver
            ->addTag('overblog_graphql.resolver', [
                'alias' => false, 'method' => 'doSomethingWithContainer',
            ]);

        $this->container->setDefinition('test_resolver', $testResolver);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service tagged "test_resolver" must have valid "alias" argument.');

        $this->addCompilerPassesAndCompile();
    }

    public function testTagMethodIsValid()
    {
        $testResolver = new Definition(ResolverTestService::class);
        $testResolver
            ->addTag('overblog_graphql.resolver', [
                'alias' => 'test_resolver', 'method' => false,
            ]);

        $this->container->setDefinition('test_resolver', $testResolver);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service tagged "test_resolver" must have valid "method" argument.');

        $this->addCompilerPassesAndCompile();
    }
}
