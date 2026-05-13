<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shared\Infrastructure\DI;

use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Infrastructure\Repository\UserRepository;
use Mossetc\TechnicalTest\Company\Domain\Repository\CompanyRepositoryInterface;
use Mossetc\TechnicalTest\Company\Infrastructure\Repository\CompanyRepository;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;
use Mossetc\TechnicalTest\Shop\Infrastructure\Repository\ShopRepository;
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

    public static function buildForTest(
        UserRepositoryInterface $repository,
        CompanyRepositoryInterface $companyRepository,
        ShopRepositoryInterface $shopRepository,
    ): ContainerInterface {
        $container = self::create();

        $container->setParameter('env(JWT_SECRET)', 'behat-test-secret-key-at-least-32-chars');
        $container->setParameter('env(JWT_ISSUER)', 'http://localhost');
        $container->setParameter('env(JWT_AUDIENCE)', 'http://localhost');
        $container->setParameter('env(PASSWORD_SALT)', '');

        $concrete = ['PDO', UserRepository::class, CompanyRepository::class, ShopRepository::class];
        foreach ($concrete as $id) {
            if ($container->hasDefinition($id)) {
                $container->removeDefinition($id);
            }
        }

        $interfaces = [
            UserRepositoryInterface::class,
            CompanyRepositoryInterface::class,
            ShopRepositoryInterface::class,
        ];
        foreach ($interfaces as $iface) {
            if ($container->hasAlias($iface)) {
                $container->removeAlias($iface);
            }
            $container->setDefinition($iface, (new Definition($iface))->setSynthetic(true));
        }

        $container->compile(resolveEnvPlaceholders: true);
        $container->set(UserRepositoryInterface::class, $repository);
        $container->set(CompanyRepositoryInterface::class, $companyRepository);
        $container->set(ShopRepositoryInterface::class, $shopRepository);

        return $container;
    }
}
