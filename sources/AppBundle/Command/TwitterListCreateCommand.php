<?php

namespace AppBundle\Command;

use AppBundle\Event\Model\Repository\EventRepository;
use AppBundle\Event\Model\Repository\SpeakerRepository;
use AppBundle\Twitter\ListCreator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TwitterListCreateCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('twitter-list:create')
            ->addArgument('event-path', null, InputArgument::REQUIRED);
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $ting = $container->get('ting');
        $event = $this->getEventFilter($input);

        $twitterListCreator = new ListCreator($container->get('app.twitter_api'), $ting->get(SpeakerRepository::class));
        $twitterListCreator->create($event);
    }

    /**
     * @param InputInterface $input
     *
     * @return null
     */
    protected function getEventFilter(InputInterface $input)
    {
        $eventPath = $input->getArgument('event-path');
        $event = $this
            ->getContainer()
            ->get('ting')
            ->get(EventRepository::class)
            ->getByPath($eventPath)
        ;

        if (null === $event) {
            throw new \InvalidArgumentException("L'événement sur lequel filter n'a pas été trouvé");
        }

        return $event;
    }
}