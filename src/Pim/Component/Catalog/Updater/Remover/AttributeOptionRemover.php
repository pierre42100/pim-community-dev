<?php
declare(strict_types=1);

namespace Pim\Component\Catalog\Updater\Remover;

use Akeneo\Component\StorageUtils\Event\RemoveEvent;
use Akeneo\Component\StorageUtils\Remover\RemoverInterface;
use Akeneo\Component\StorageUtils\StorageEvents;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Pim\Bundle\CatalogBundle\Elasticsearch\ProductAndProductModelQueryBuilderFactory;
use Pim\Component\Catalog\Model\AttributeOptionInterface;
use Pim\Component\Catalog\Repository\FamilyVariantRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Removes an attribute option only if it is not use as a family variant axis.
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AttributeOptionRemover implements RemoverInterface
{
    /** @var ObjectManager */
    private $objectManager;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var FamilyVariantRepositoryInterface */
    private $familyVariantRepository;

    /** @var ProductAndProductModelQueryBuilderFactory */
    private $pqbFactory;

    /**
     * @param ObjectManager                             $objectManager
     * @param EventDispatcherInterface                  $eventDispatcher
     * @param FamilyVariantRepositoryInterface          $familyVariantRepository
     * @param ProductAndProductModelQueryBuilderFactory $pqbFactory
     */
    public function __construct(
        ObjectManager $objectManager,
        EventDispatcherInterface $eventDispatcher,
        FamilyVariantRepositoryInterface $familyVariantRepository,
        ProductAndProductModelQueryBuilderFactory $pqbFactory
    ) {
        $this->objectManager = $objectManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->familyVariantRepository = $familyVariantRepository;
        $this->pqbFactory = $pqbFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($attributeOption, array $options = []): void
    {
        $this->ensureIsAnAttributeOption($attributeOption);
        $attributeCode = $attributeOption->getAttribute()->getCode();
        $familyVariantsIdentifier = $this->familyVariantRepository->findIdentifierByAttributeAxes([$attributeCode]);

        $pqb = $this->pqbFactory->create();

        //$this->processRemove($attributeOption);
    }

    /**
     * @param mixed $object
     *
     * @throws \LogicException
     */
    private function ensureIsAnAttributeOption($object): void
    {
        if (!$object instanceof AttributeOptionInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expects a "%s", "%s" provided.',
                    AttributeOptionInterface::class,
                    ClassUtils::getClass($object)
                )
            );
        }
    }

    /**
     * @param AttributeOptionInterface $attributeOption
     */
    private function processRemove(AttributeOptionInterface $attributeOption): void
    {
        $this->sendEvent($attributeOption, StorageEvents::PRE_REMOVE);

        $this->objectManager->remove($attributeOption);
        $this->objectManager->flush();

        $this->sendEvent($attributeOption, StorageEvents::POST_REMOVE);
    }

    /**
     * @param AttributeOptionInterface $attributeOption
     * @param string $event
     */
    private function sendEvent(AttributeOptionInterface $attributeOption, string $event): void
    {
        $this->eventDispatcher->dispatch(
            $event,
            new RemoveEvent($attributeOption, $attributeOption->getId(), ['unitary' => true])
        );
    }
}
