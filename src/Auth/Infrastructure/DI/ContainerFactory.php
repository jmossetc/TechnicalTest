<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\DI;

use Mossetc\TechnicalTest\Auth\Domain\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Auth\Infrastructure\Repository\PdoUserRepository;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class ContainerFactory
{
    private static function create(): ContainerBuilder
    {
        $projectDir = dirname(__DIR__, 4);

        $container = new ContainerBuilder();
        $container->setParameter('routes.config', $projectDir . '/config/routes.yaml');
        $container->setParameter('jwt.ttl', (int) (getenv('JWT_TTL') ?: 3600));

        $container->registerAttributeForAutoconfiguration(
            AsHttpController::class,
            static function (ChildDefinition $definition, AsHttpController $attribute): void {
                $definition->addTag('app.http_controller', ['route' => $attribute->route]);
            },
        );

        $loader = new YamlFileLoader($container, new FileLocator($projectDir . '/config'));
        $loader->load('services.yaml');

        return $container;
    }

    public static function build(): ContainerInterface
    {
        $container = self::create();
        $container->compile(resolveEnvPlaceholders: true);

        return $container;
    }

    /**
     * Builds a container suitable for testing: removes PDO infrastructure and
     * registers the given repository as a synthetic service so it is injected
     * wherever UserRepositoryInterface is required.
     */
    public static function buildForTest(UserRepositoryInterface $repository): ContainerInterface
    {
        $container = self::create();

        // Provide test JWT credentials so the JwtConfig validation passes
        $container->setParameter('env(JWT_SECRET)', 'behat-test-secret-key-at-least-32-chars');
        $container->setParameter('env(JWT_ISSUER)', 'http://localhost');
        $container->setParameter('env(JWT_AUDIENCE)', 'http://localhost');

        // Strip out the database layer — it is not needed in tests
        foreach (['PDO', PdoUserRepository::class] as $id) {
            if ($container->hasDefinition($id)) {
                $container->removeDefinition($id);
            }
        }

        // Replace the alias with a synthetic placeholder that will be satisfied
        // by the test double after compilation
        if ($container->hasAlias(UserRepositoryInterface::class)) {
            $container->removeAlias(UserRepositoryInterface::class);
        }
        $container->setDefinition(
            UserRepositoryInterface::class,
            (new Definition(UserRepositoryInterface::class))->setSynthetic(true),
        );

        $container->compile(resolveEnvPlaceholders: true);
        $container->set(UserRepositoryInterface::class, $repository);

        return $container;
    }
}
