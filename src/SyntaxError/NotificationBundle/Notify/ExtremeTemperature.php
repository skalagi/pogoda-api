<?php

namespace SyntaxError\NotificationBundle\Notify;

use Symfony\Component\DependencyInjection\ContainerInterface;
use SyntaxError\ApiBundle\Entity\ArchiveDayOuttemp;
use SyntaxError\NotificationBundle\Date;
use SyntaxError\NotificationBundle\Kernel\NotifyInterface;

class ExtremeTemperature implements NotifyInterface
{
    private $type;

    private $period;

    private $record = false;

    public function isActive(ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.default_entity_manager');
        $todayRecord = $em->getRepository("SyntaxErrorApiBundle:ArchiveDayOuttemp")->findOneByDay(new \DateTime);
        $dayOfMonth = (new \DateTime('now'))->format("d");
        if($dayOfMonth < 7) return false;

        $date = new Date();

        $monthMax = $em->getRepository("SyntaxErrorApiBundle:ArchiveDayOuttemp")->createQueryBuilder('a')
            ->where('a.datetime >= :from')->andWhere('a.datetime <= :to')->andWhere('a.datetime != :this')
            ->setParameter('from', $date->getMonthStart()->getTimestamp())->setParameter('to', $date->getMonthEnd()->getTimestamp())
            ->setParameter('this', $todayRecord->getDatetime())->orderBy('a.max', 'desc')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
        $monthMin = $em->getRepository("SyntaxErrorApiBundle:ArchiveDayOuttemp")->createQueryBuilder('a')
            ->where('a.datetime >= :from')->andWhere('a.datetime <= :to')->andWhere('a.datetime != :this')
            ->setParameter('from', $date->getMonthStart()->getTimestamp())->setParameter('to', $date->getMonthEnd()->getTimestamp())
            ->setParameter('this', $todayRecord->getDatetime())->orderBy('a.min', 'asc')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
        $yearMax = $em->getRepository("SyntaxErrorApiBundle:ArchiveDayOuttemp")->createQueryBuilder('a')
            ->where('a.datetime >= :from')->andWhere('a.datetime <= :to')->andWhere('a.datetime != :this')
            ->setParameter('from', $date->getYearStart()->getTimestamp())->setParameter('to', $date->getYearEnd()->getTimestamp())
            ->setParameter('this', $todayRecord->getDatetime())->orderBy('a.max', 'desc')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
        $yearMin = $em->getRepository("SyntaxErrorApiBundle:ArchiveDayOuttemp")->createQueryBuilder('a')
            ->where('a.datetime >= :from')->andWhere('a.datetime <= :to')->andWhere('a.datetime != :this')
            ->setParameter('from', $date->getYearStart()->getTimestamp())->setParameter('to', $date->getYearEnd()->getTimestamp())
            ->setParameter('this', $todayRecord->getDatetime())->orderBy('a.min', 'asc')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();


        if($todayRecord->getMax() > $monthMax->getMax()) {
            $this->type = "wysoka";
            $this->period = "miesiącu";
            $this->record = $todayRecord;
            return true;
        }
        if($todayRecord->getMin() < $monthMin->getMin()) {
            $this->type = "niska";
            $this->period = "miesiącu";
            $this->record = $todayRecord;
            return true;
        }
        if($todayRecord->getMax() > $yearMax->getMax()) {
            $this->type = "wysoka";
            $this->period = "roku";
            $this->record = $todayRecord;
            return true;
        }
        if($todayRecord->getMin() < $yearMin->getMin()) {
            $this->type = "niska";
            $this->period = "roku";
            $this->record = $todayRecord;
            return true;
        }

        return false;
    }

    public function getName()
    {
        return "[ALERT] Zanotowana ekstremalnie ".$this->type." temperatura.";
    }

    public function getContent(\Twig_Environment $twig, array $additional = [])
    {
        return $twig->render('Extreme/temperature.html.twig', array_merge([
            'type' => $this->type,
            'period' => $this->period,
            'record' => $this->record
        ], $additional));
    }
}
