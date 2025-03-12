<?php

namespace Feral\Agent\Process\Event\Listener;

use Feral\Agent\Process\DataCollector\Trace\ProcessTraceCollectorInterface;
use Feral\Core\Process\Event\ProcessEndEvent;
use Feral\Core\Process\Event\ProcessNodeAfterEvent;
use Feral\Core\Process\Event\ProcessNodeBeforeEvent;
use Feral\Core\Process\Event\ProcessStartEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
class ProfilerProcessSubscriber implements EventSubscriberInterface
{
    public function __construct(private ProcessTraceCollectorInterface $collector){}

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProcessStartEvent::class => ['onStartEvent'],
            ProcessEndEvent::class => ['onEndEvent'],
            ProcessNodeBeforeEvent::class => ['onNodeBeforeEvent'],
            ProcessNodeAfterEvent::class => ['onNodeAfterEvent']
        ];
    }

    public function onStartEvent(ProcessStartEvent $event): void
    {
        $this->collector->start($event->getProcess(), $event->getContext());
    }

    public function onNodeBeforeEvent(ProcessNodeBeforeEvent $event): void
    {
        $this->collector->startNode($event->getNode(), $event->getContext());
    }

    public function onNodeAfterEvent(ProcessNodeAfterEvent $event): void
    {
        $this->collector->endNode($event->getResult(), $event->getContext());
    }

    public function onEndEvent(ProcessEndEvent $event): void
    {
        $this->collector->end($event->getContext());
    }
}