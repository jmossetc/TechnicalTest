<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shared\Infrastructure\DI;

use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRoleRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Infrastructure\Repository\UserRepository;
use Mossetc\TechnicalTest\Auth\Infrastructure\Repository\UserRoleRepository;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
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
    public static function buildForTest(
        UserRepositoryInterface $repository,
        UserRoleRepositoryInterface $roleRepository,
    ): ContainerInterface {
        $container = self::create();

        // Provide test JWT credentials so the JwtConfig validation passes
        $container->setParameter('env(JWT_SECRET)', 'behat-test-secret-key-at-least-32-chars');
        $container->setParameter('env(JWT_ISSUER)', 'http://localhost');
        $container->setParameter('env(JWT_AUDIENCE)', 'http://localhost');

        // Strip out the database layer — it is not needed in tests
        foreach (['PDO', UserRepository::class, UserRoleRepository::class] as $id) {
            if ($container->hasDefinition($id)) {
                $container->removeDefinition($id);
            }
        }

        // Replace aliases with synthetic placeholders that will be satisfied
        // by the test doubles after compilation
        foreach ([UserRepositoryInterface::class, UserRoleRepositoryInterface::class] as $iface) {
            if ($container->hasAlias($iface)) {
                $container->removeAlias($iface);
            }
            $container->setDefinition(
                $iface,
                (new Definition($iface))->setSynthetic(true),
            );
        }

        $container->compile(resolveEnvPlaceholders: true);
        $container->set(UserRepositoryInterface::class, $repository);
        $container->set(UserRoleRepositoryInterface::class, $roleRepository);

        return $container;
    }
}
