<?php
declare(strict_types=1);

namespace BitBag\SyliusAgreementPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\AbstractResourceFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class AgreementFixture extends AbstractResourceFixture
{
    public function getName(): string
    {
        return 'agreement';
    }

    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        $resourceNode
            ->children()
                ->scalarNode('name')->cannotBeEmpty()->end()
                ->scalarNode('code')->cannotBeEmpty()->end()
                ->variableNode('contexts')
                    ->beforeNormalization()
                        ->ifNull()->thenUnset()
                    ->end()
                ->end()
                ->scalarNode('mode')->cannotBeEmpty()->end()
                ->scalarNode('body')->cannotBeEmpty()->end()
                ->scalarNode('extended_body')->cannotBeEmpty()->end()
                ->variableNode('translations')->cannotBeEmpty()->defaultValue([])->end()
                ->variableNode('children')->cannotBeEmpty()->defaultValue([])->end()
                ->variableNode('customers')
                    ->beforeNormalization()
                        ->ifNull()->thenUnset()
                    ->end()
                ->end()
        ;
    }
}
