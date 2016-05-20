<?php

namespace SyntaxError\NotificationBundle\Notify;

use Symfony\Component\DependencyInjection\ContainerInterface;
use SyntaxError\ApiBundle\Entity\ArchiveDayWindgust;
use SyntaxError\ApiBundle\Tools\Uniter;
use SyntaxError\NotificationBundle\Date;
use SyntaxError\NotificationBundle\Kernel\NotifyInterface;

class ExtremeWind implements NotifyInterface
{
    private $period;

    private $record = [];

    public function isActive(ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.default_entity_manager');
        $todayRecordSpeed = $em->getRepository("SyntaxErrorApiBundle:ArchiveDayWindgust")->findOneByDay(new \DateTime);

        $dayOfMonth = (new \DateTime('now'))->format("d");
        if($dayOfMonth < 7) return false;

        $date = new Date();

        $monthMax = $em->getRepository("SyntaxErrorApiBundle:ArchiveDayWindgust")->createQueryBuilder('a')
            ->where('a.datetime >= :from')->andWhere('a.datetime <= :to')->andWhere('a.datetime != :this')
            ->setParameter('from', $date->getMonthStart()->getTimestamp())->setParameter('to', $date->getMonthEnd()->getTimestamp())
            ->setParameter('this', $todayRecordSpeed->getDatetime())->orderBy('a.max', 'desc')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        $yearMax = $em->getRepository("SyntaxErrorApiBundle:ArchiveDayWindgust")->createQueryBuilder('a')
            ->where('a.datetime >= :from')->andWhere('a.datetime <= :to')->andWhere('a.datetime != :this')
            ->setParameter('from', $date->getYearStart()->getTimestamp())->setParameter('to', $date->getYearEnd()->getTimestamp())
            ->setParameter('this', $todayRecordSpeed->getDatetime())->orderBy('a.max', 'desc')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if($todayRecordSpeed->getMax() > $monthMax->getMax()) {
            $this->period = "miesiąca";
            $this->record['speed'] = $todayRecordSpeed;
            $this->record['dir'] = $em->getRepository("SyntaxErrorApiBundle:Archive")->findProximate(
                (new \DateTime())->setTimestamp($todayRecordSpeed->getMaxtime())
            )->getWindGustDir();
            return true;
        }

        if($todayRecordSpeed->getMax() > $yearMax->getMax()) {
            $this->period = "roku";
            $this->record['speed'] = $todayRecordSpeed;
            $this->record['dir'] = $em->getRepository("SyntaxErrorApiBundle:Archive")->findProximate(
                (new \DateTime())->setTimestamp($todayRecordSpeed->getMaxtime())
            )->getWindGustDir();
            return true;
        }

        return false;
    }

    public function getName()
    {
        return "[ALERT] Zanotowano ekstremalnie silne porywy wiatru.";
    }

    public function getContent(\Twig_Environment $twig, array $additional = [])
    {
        return $twig->render('Extreme/wind.html.twig', array_merge([
            'period' => $this->period,
            'record' => $this->record['speed'],
            'translatedDir' => Uniter::windDirPl($this->record['dir'])
        ], $additional));
    }
}
