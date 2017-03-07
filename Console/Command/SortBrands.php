<?php

namespace Room204\SortBrands\Console\Command;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;


/**
 * Created by PhpStorm.
 * User: markcyrulik
 * Date: 11/1/16
 * Time: 6:58 PM
 */
class SortBrands extends Command
{
    protected $_attributeRepository;

    protected $_registry;

    protected $_state;

    protected $_optionCollection;

    protected $_timezoneInterface;

    protected $_dateTime;

    /**
     * Constructor
     */
    public function __construct(
        \Magento\Framework\App\State $state,
        Registry $registry,
        AttributeRepositoryInterface $attributeRepository,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection $optionCollection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        DateTime $dateTime
    ) {
        $this->_attributeRepository = $attributeRepository;
        $this->_registry = $registry;
        $this->_state = $state;
        $this->_optionCollection = $optionCollection;
        $this->_timezoneInterface = $timezoneInterface;
        $this->_dateTime = $dateTime;

        parent::__construct();
    }

    /**
     *
     */
    protected function configure()
    {
        $this->setName('room204:sortbrands')
            ->setDescription('Sort the brands.');
        parent::configure();;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_registry->register('isSecureArea', true);
        $this->_state->setAreaCode('adminhtml');

        $output->writeln('Sorting Brands');

        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute\Interceptor $manufacturer */
        $manufacturer = $this->_attributeRepository->get(\Magento\Catalog\Model\Product::ENTITY, 'manufacturer');

        $allOptions = $manufacturer->getOptions();

        $optionList = [];

        foreach ($allOptions as $text) {
            $optionList[$text->getData('value')] = $text->getData('label');
        }

        $brandOptions = $this->_optionCollection->addFieldToFilter('attribute_id', [ $manufacturer->getId()]);

        $newSort = [];

        /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
        foreach ($brandOptions as $option) {
            //var_dump($option->getData());
            $newSort[] = [
                'id' => $option->getId(),
                'sort_order' => $option->getSortOrder(),
                'label' => $optionList[$option->getId()]
            ];
        }

        usort($newSort, function($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });

        $newOptionList = [];
        foreach ($newSort as $key => $item) {
            $output->writeln($item['id'].": ".$key." => ".$item['label']);
            $newOptionList[$item['id']] = $key+1;
        }


        /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
        foreach ($brandOptions as $option) {
            $option->setSortOrder($newOptionList[$option->getId()])->save();
        }

        $output->writeln('DONE');

    }


}