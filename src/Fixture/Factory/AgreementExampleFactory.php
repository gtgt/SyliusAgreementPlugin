<?php
declare(strict_types=1);

namespace BitBag\SyliusAgreementPlugin\Fixture\Factory;

use App\Entity\Customer\Customer;
use BitBag\SyliusAgreementPlugin\DataModifier\AgreementHistoryModifierInterface;
use BitBag\SyliusAgreementPlugin\Entity\Agreement\AgreementHistoryStates;
use BitBag\SyliusAgreementPlugin\Entity\Agreement\AgreementInterface;
use BitBag\SyliusAgreementPlugin\Repository\AgreementHistoryRepository;
use BitBag\SyliusAgreementPlugin\Repository\AgreementRepositoryInterface;
use Faker\Factory;
use Faker\Generator;
use Sylius\Bundle\CoreBundle\Fixture\Factory\AbstractExampleFactory;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Bundle\CoreBundle\Fixture\OptionsResolver\LazyOption;
use Sylius\Component\Core\Formatter\StringInflector;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgreementExampleFactory extends AbstractExampleFactory implements ExampleFactoryInterface {
    private Generator $faker;

    private OptionsResolver $optionsResolver;

    public function __construct(
        private FactoryInterface $agreementFactory,
        private AgreementRepositoryInterface $agreementRepository,
        private AgreementHistoryModifierInterface $agreementHistoryModifier,
        private AgreementHistoryRepository $agreementHistoryRepository,
        private RepositoryInterface $customerRepository,
        private RepositoryInterface $localeRepository,
        private array $modes,
        private array $contexts,
    )
    {
        $this->faker = Factory::create();
        $this->optionsResolver = new OptionsResolver();

        $this->configureOptions($this->optionsResolver);
    }

    public function create(array $options = []): AgreementInterface
    {
        return $this->createAgreement($options);
    }

    protected function createAgreement(array $options = [], ?AgreementInterface $parentAgreement = null): ?AgreementInterface
    {
        $options = $this->optionsResolver->resolve($options);

        /** @var AgreementInterface|null $agreement */
        $agreement = $this->agreementRepository->findOneBy(['code' => $options['code']]);

        if (null === $agreement) {
            /** @var AgreementInterface $agreement */
            $agreement = $this->agreementFactory->createNew();
        }

        $agreement->setCode($options['code']);
        $agreement->setMode($options['mode']);
        $agreement->setContexts($options['contexts']);

        if (null !== $parentAgreement) {
            $agreement->setParent($parentAgreement);
        }

        // add translation for each defined locales
        foreach ($this->getLocales() as $localeCode) {
            $this->createTranslation($agreement, $localeCode, $options);
        }

        // create or replace with custom translations
        foreach ($options['translations'] as $localeCode => $translationOptions) {
            $this->createTranslation($agreement, $localeCode, $translationOptions);
        }

        $this->agreementRepository->add($agreement);

        foreach ($options['children'] as $childOptions) {
            $this->createAgreement($childOptions, $agreement);
        }

        /** @var Customer $customer */
        foreach ($options['customers'] as $customer) {
            $agreement->setApproved(true);
            $context = array_rand(array_flip($options['contexts']));
            $agreementHistory = $this->agreementHistoryModifier->setAgreementHistoryProperties($context, null, $customer->getUser(), $agreement);
            $agreementHistory->setState(AgreementHistoryStates::STATE_ACCEPTED);
            $this->agreementHistoryRepository->add($agreementHistory);

        }

        return $agreement;
    }

    protected function createTranslation(AgreementInterface $agreement, string $localeCode, array $options = []): void
    {
        $options = $this->optionsResolver->resolve($options);

        $agreement->setCurrentLocale($localeCode);
        $agreement->setFallbackLocale($localeCode);

        $agreement->setName($options['name']);
        $agreement->setBody($options['body']);
        $agreement->setExtendedBody($options['extended_body']);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('name', function(Options $options): string {
                /** @var string $words */
                $words = $this->faker->words(3, true);

                return $words;
            })
            ->setDefault('code', fn(Options $options): string => StringInflector::nameToCode($options['name']))
            ->setDefault('contexts', fn(Options $options): array => (array)array_rand($this->contexts, random_int(1, count($this->contexts))))
            ->setAllowedTypes('contexts', 'array')
            ->setAllowedValues('contexts', fn(array $value): bool => !array_diff($value, array_keys($this->contexts)))
            ->setDefault('mode', fn(Options $options): string => $this->modes[random_int(0, count($this->modes) - 1)])
            ->setAllowedValues('mode', $this->modes)
            ->setDefault('body', fn(Options $options): string => $this->faker->paragraph)
            ->setDefault('extended_body', fn(Options $options): ?string => random_int(1, 100) > 20 ? $this->faker->paragraphs(3, true) : null)
            ->setDefault('translations', [])
            ->setAllowedTypes('translations', 'array')
            ->setDefault('children', [])
            ->setAllowedTypes('children', 'array')
            ->setDefault('customers', fn(Options $options) => random_int(1, 100) < 30 ? LazyOption::randomOnes($this->customerRepository, 5)($options) : [])
            //->setDefault('customers', LazyOption::randomOnes($this->customerRepository, 5))
            ->setAllowedTypes('customers', 'array')
            ->setNormalizer('customers', LazyOption::findBy($this->customerRepository, 'id'));
    }

    private function getLocales(): iterable
    {
        /** @var LocaleInterface[] $locales */
        $locales = $this->localeRepository->findAll();
        foreach ($locales as $locale) {
            yield $locale->getCode();
        }
    }
}
