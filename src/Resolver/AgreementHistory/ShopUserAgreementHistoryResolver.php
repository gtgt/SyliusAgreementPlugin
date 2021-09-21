<?php

declare(strict_types=1);

namespace BitBag\SyliusAgreementPlugin\Resolver\AgreementHistory;

use BitBag\SyliusAgreementPlugin\Entity\Agreement\AgreementHistoryInterface;
use BitBag\SyliusAgreementPlugin\Entity\Agreement\AgreementInterface;
use BitBag\SyliusAgreementPlugin\Repository\AgreementHistoryRepositoryInterface;
use BitBag\SyliusAgreementPlugin\Resolver\AgreementHistoryResolverInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Symfony\Component\Security\Core\Security;

final class ShopUserAgreementHistoryResolver implements AgreementHistoryResolverInterface
{
    private Security $security;

    private AgreementHistoryRepositoryInterface $agreementHistoryRepository;

    public function __construct(
        Security $security,
        AgreementHistoryRepositoryInterface $agreementHistoryRepository
    )
    {
        $this->security = $security;
        $this->agreementHistoryRepository = $agreementHistoryRepository;
    }

    public function resolveHistory(AgreementInterface $agreement): ?AgreementHistoryInterface
    {
        /** @var ShopUserInterface $shopUser */
        $shopUser = $this->security->getUser();

        if ($agreement->getId() !== null && $shopUser->getId() !== null) {
            /** @var AgreementHistoryInterface|null $agreementHistory */
            $agreementHistory = $this->agreementHistoryRepository->findOneForShopUser($agreement, $shopUser);

            return $agreementHistory;
        }

        return null;
    }

    public function supports(AgreementInterface $agreement): bool
    {
        return $this->security->getUser() instanceof ShopUserInterface;
    }
}
