<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\TestHelper\Data;

use BadMethodCallException;
use Faker\Factory;
use Faker\Generator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\IndexerInterfaceFactory;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\WebsiteFactory;

class StoreProvider
{
    /**
     * @var Generator
     */
    private $faker;

    /**
     * @var EntityHydrator
     */
    private $hydrator;

    /**
     * @var WebsiteFactory
     */
    private $websiteFactory;

    /**
     * @var GroupFactory
     */
    private $groupFactory;

    /**
     * @var StoreFactory
     */
    private $storeFactory;

    /**
     * @var CategoryProvider
     */
    private $categoryProvider;

    /**
     * @var IndexerInterfaceFactory
     */
    private $indexerFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * StoreProvider constructor.
     *
     * @param EntityHydrator $hydrator
     * @param WebsiteFactory $websiteFactory
     * @param GroupFactory $groupFactory
     * @param StoreFactory $storeFactory
     * @param CategoryProvider $categoryProvider
     * @param StoreManagerInterface $storeManager
     * @param IndexerInterfaceFactory $indexerFactory
     */
    public function __construct(
        EntityHydrator $hydrator,
        WebsiteFactory $websiteFactory,
        GroupFactory $groupFactory,
        StoreFactory $storeFactory,
        CategoryProvider $categoryProvider,
        StoreManagerInterface $storeManager,
        IndexerInterfaceFactory $indexerFactory
    )
    {
        $this->faker = Factory::create();
        $this->hydrator = $hydrator;
        $this->websiteFactory = $websiteFactory;
        $this->groupFactory = $groupFactory;
        $this->storeFactory = $storeFactory;
        $this->categoryProvider = $categoryProvider;
        $this->indexerFactory = $indexerFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $code
     * @return WebsiteInterface|null
     */
    public function getWebsite(string $code)
    {
        try {
            return $this->storeManager->getWebsite($code);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Ensure website is created if it does not exists.
     *
     * @param array $data
     * @return WebsiteInterface
     */
    public function createWebsite(array $data = []): WebsiteInterface
    {
        $website = $this->websiteFactory->create();
        $website->setName($this->faker->firstName());
        $website->setCode($this->sanitizeCode($website->getName()));
        $website->setSortOrder(10);

        $this->hydrator->hydrate($data, $website);

        $existingWebsite = $this->getWebsite($website->getCode());
        if ($existingWebsite) {
            return $existingWebsite;
        }

        $website->save();

        return $website;
    }

    /**
     * Ensure website is removed, does not doe anything if website did not exist.
     * Will return true if website was found and removed, otherwise will return false.
     *
     * @param string $code
     * @return bool
     */
    public function removeWebsite(string $code): bool
    {
        $website = $this->getWebsite($code);
        if ($website === null) {
            return false;
        }

        if (!method_exists($website, 'delete')) {
            throw new BadMethodCallException(sprintf('Method delete not found on %s', \get_class($website)));
        }

        $website->delete();
        return true;
    }

    /**
     * @param string $code
     * @return GroupInterface|null
     */
    public function getStoreGroup(string $code)
    {
        try {
            // Must use getGroups here even the API reports getGroup should return also with group code, it does not.
            foreach ($this->storeManager->getGroups(true) as $group) {
                if ($group->getCode() === $code) {
                    return $group;
                }
            }

            return null;
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Ensure store group is created if it does not exists.
     *
     * @param WebsiteInterface $website
     * @param array $data
     * @return GroupInterface
     */
    public function createStoreGroup(WebsiteInterface $website = null, array $data = []): GroupInterface
    {
        if ($website === null) {
            $website = $this->createWebsite();
        }

        $group = $this->groupFactory->create();
        $group->setName($this->faker->domainName);
        $group->setCode($this->sanitizeCode($website->getCode() . strtolower($group->getName())));
        $group->setWebsiteId($website->getId());
        $group->setRootCategoryId($this->categoryProvider->getDefaultRootId());

        $this->hydrator->hydrate($data, $group);

        $existingGroup = $this->getStoreGroup($group->getCode());
        if ($existingGroup) {
            return $existingGroup;
        }

        $group->save();

        return $group;
    }

    /**
     * Ensure store group is removed, does not doe anything if store group did not exist.
     * Will return true if store group was found and removed, otherwise will return false.
     *
     * @param string $code
     * @return bool
     */
    public function removeStoreGroup(string $code): bool
    {
        $group = $this->getStoreGroup($code);
        if ($group === null) {
            return false;
        }

        if (!method_exists($group, 'delete')) {
            throw new BadMethodCallException(sprintf('Method delete not found on %s', \get_class($group)));
        }

        $group->delete();
        return true;
    }

    /**
     * @param string $code
     * @return StoreInterface|null
     */
    public function getStoreView(string $code)
    {
        try {
            return $this->storeManager->getStore($code);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * @param GroupInterface|null $group
     * @param array $data
     * @return StoreInterface
     */
    public function createStoreView(GroupInterface $group = null, array $data = []): StoreInterface
    {
        if ($group === null) {
            $group = $this->createStoreGroup();
        }

        $store = $this->storeFactory->create();
        $store->setName($this->faker->domainName);
        $store->setCode($this->sanitizeCode($store->getName()));
        $store->setWebsiteId($group->getWebsiteId());
        $store->setGroupId($group->getId());
        $store->setSortOrder(10);
        $store->setIsActive(true);

        $this->hydrator->hydrate($data, $store);

        $existingStore = $this->getStoreView($store->getCode());
        if ($existingStore) {
            return $existingStore;
        }

        $store->save();

        $this->reindex();

        return $store;
    }

    /**
     * Ensure store view is removed, does not doe anything if store view did not exist.
     * Will return true if store view was found and removed, otherwise will return false.
     *
     * @param string $code
     * @return bool
     */
    public function removeStoreView(string $code): bool
    {
        $store = $this->getWebsite($code);
        if ($store === null) {
            return false;
        }

        if (!method_exists($store, 'delete')) {
            throw new BadMethodCallException(sprintf('Method delete not found on %s', \get_class($store)));
        }

        $store->delete();
        return true;
    }

    /**
     * Reindex flat and full text so we have the required tables
     */
    protected function reindex()
    {
        $indexer = $this->indexerFactory->create();
        $indexer->load('catalogsearch_fulltext');
        $indexer->reindexAll();
    }

    /**
     * @param string $code
     * @return string
     */
    protected function sanitizeCode(string $code): string
    {
        $code = strtolower($code);
        $code = preg_replace('/[^a-zA-Z0-9]/', '_', $code);
        return $code;
    }
}