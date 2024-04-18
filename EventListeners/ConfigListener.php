<?php

namespace CustomDelivery\EventListeners;

use CustomDelivery\CustomDelivery;
use CustomDelivery\Model\CustomDeliverySliceQuery;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Thelia\Model\AreaDeliveryModuleQuery;
use Thelia\Model\ModuleConfigQuery;
use Thelia\Model\ModuleQuery;

class ConfigListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'module.config' => [
                'onModuleConfig', 128
                ],
        ];
    }

    public function onModuleConfig(GenericEvent $event): void
    {
        $subject = $event->getSubject();

        if ($subject !== "HealthStatus") {
            throw new \RuntimeException('Event subject does not match expected value');
        }

        $shippingZoneConfig = AreaDeliveryModuleQuery::create()
            ->filterByDeliveryModuleId(CustomDelivery::getModuleId())
            ->find();

        $configModule = CustomDeliverySliceQuery::create()
            ->find();

        $moduleConfig = [];
        $moduleConfig['module'] = CustomDelivery::getModuleCode();
        $configsCompleted = true;

        if ($configModule->count() === 0) {
            $configsCompleted = false;
        }

        if ($shippingZoneConfig->count() === 0) {
            $configsCompleted = false;
        }

        $moduleConfig['completed'] = $configsCompleted;

        $event->setArgument('delivery.module.config', $moduleConfig);
    }
}