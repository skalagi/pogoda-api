<?php

namespace SyntaxError\ApiBundle\Service;

use Doctrine\ORM\EntityManager;
use SyntaxError\ApiBundle\Entity\ArchiveDayOuttemp;
use SyntaxError\ApiBundle\Entity\ArchiveDayWindgustdir;
use SyntaxError\ApiBundle\Tools\ArchiveDay;
use SyntaxError\ApiBundle\Weather\MaxMin;
use SyntaxError\ApiBundle\Weather\Reading;

class MonthService
{
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function highDoubleFormatter(\DateTime $dateTime, $archiveName)
    {
        $from = (new \DateTime( $dateTime->format("Y-m-01 00:00:00") ))->getTimestamp()+3600*20;
        $to = (new \DateTime( $dateTime->format("Y-m-t 23:59:59") ))->getTimestamp()+3600*20;

        $records = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDay$archiveName")
            ->createQueryBuilder('a')->where('a.datetime BETWEEN :from AND :to')
            ->setParameter('from', $from)->setParameter('to', $to)
            ->orderBy('a.datetime', 'asc')->getQuery()->getResult();
        $output = [];

        switch($archiveName) {
            case 'Windgustdir':
                foreach($records as $i => $record) {
                    if($record instanceof ArchiveDay) {
                        if( (new \DateTime())->setTimestamp( $record->getDatetime() )->format("H") != 0) continue;
                        $cnt = $record->getCount();
                        $output[] = [$record->getDatetime()-5, $cnt ? $record->getSum() / $cnt : 0];
                    }
                } break;

            case 'Rain':
                foreach($records as $i => $record) {
                    if($record instanceof ArchiveDay) {
                        if( (new \DateTime())->setTimestamp( $record->getDatetime() )->format("H") != 0) continue;
                        $output[] = [$record->getDatetime()-5, $record->getSum()];
                    }
                } break;

            default:
                foreach($records as $i => $record) {
                    if($record instanceof ArchiveDay) {
                        if( (new \DateTime())->setTimestamp( $record->getDatetime() )->format("H") != 0) continue;
                        $output[] = [$record->getDatetime()-5, $record->getMin(), $record->getMax()];
                    }
                } break;
        }

        return $output;
    }

    public function createTemperature(\DateTime $dateTime)
    {
        $max = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayOuttemp")->findMonthRecord($dateTime);
        $min = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayOuttemp")->findMonthRecord($dateTime, false);
        if(!$max || !$min) return "Empty record.";
        $maxTemp = new Reading();
        $maxTemp->value = $max->getMax();
        $maxTemp->units = '℃';
        $maxTemp->name = (new \DateTime())->setTimestamp( $max->getMaxtime() )->format("Y-m-d H:i:s");

        $minTemp = new Reading();
        $minTemp->value = $min->getMin();
        $minTemp->units = '℃';
        $minTemp->name = (new \DateTime())->setTimestamp( $min->getMintime() )->format("Y-m-d H:i:s");

        $maxMin = new MaxMin();
        $maxMin->setMax($maxTemp);
        return $maxMin->setMin($minTemp);
    }

    public function createHumidity(\DateTime $dateTime)
    {
        $max = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayOuthumidity")->findMonthRecord($dateTime);
        $min = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayOuthumidity")->findMonthRecord($dateTime, false);
        if(!$max || !$min) return "Empty record.";
        $maxHumidity = new Reading();
        $maxHumidity->value = $max->getMax();
        $maxHumidity->units = '%';
        $maxHumidity->name = (new \DateTime())->setTimestamp( $max->getMaxtime() )->format("Y-m-d H:i:s");

        $minHumidity = new Reading();
        $minHumidity->value = $min->getMin();
        $minHumidity->units = '%';
        $minHumidity->name = (new \DateTime())->setTimestamp( $min->getMintime() )->format("Y-m-d H:i:s");

        $maxMin = new MaxMin();
        $maxMin->setMax($maxHumidity);
        return $maxMin->setMin($minHumidity);
    }

    public function createBarometer(\DateTime $dateTime)
    {
        $max = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayBarometer")->findMonthRecord($dateTime);
        $min = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayBarometer")->findMonthRecord($dateTime, false);
        if(!$max || !$min) return "Empty record.";
        $maxBaro = new Reading();
        $maxBaro->value = $max->getMax();
        $maxBaro->units = 'hPa';
        $maxBaro->name = (new \DateTime())->setTimestamp( $max->getMaxtime() )->format("Y-m-d H:i:s");

        $minBaro = new Reading();
        $minBaro->value = $min->getMin();
        $minBaro->units = 'hPa';
        $minBaro->name = (new \DateTime())->setTimestamp( $min->getMintime() )->format("Y-m-d H:i:s");

        $maxMin = new MaxMin();
        $maxMin->setMax($maxBaro);
        return $maxMin->setMin($minBaro);
    }

    public function createWindSpeed(\DateTime $dateTime)
    {
        $max = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayWindgust")->findMonthRecord($dateTime);
        if(!$max) return "Empty record.";
        $windSpeed = new Reading();
        $windSpeed->value = $max->getMax();
        $windSpeed->units = 'km/h';
        $windSpeed->name = (new \DateTime())->setTimestamp( $max->getMaxtime() )->format("Y-m-d H:i:s");

        return ['max' => $windSpeed];
    }

    public function createWindDir(\DateTime $dateTime)
    {
        $avg = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayWindgustdir")->avgMonth($dateTime);

        $reading = new Reading();
        $reading->name = "Średni kierunek wiatru";
        $reading->units = "deg";
        $reading->value = $avg;
        return $reading;
    }

    public function createRain(\DateTime $dateTime)
    {
        $sum = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayRain")->findMonthSum($dateTime);

        $reading = new Reading();
        $reading->name = "Suma opadów";
        $reading->value = $sum*25.4;
        $reading->units = "mm";
        return $reading;
    }

    public function createRainRate(\DateTime $dateTime)
    {
        $max = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayRainrate")->findMonthRecord($dateTime);
        if(!$max) return "Empty record.";
        $rainRate = new Reading();
        $rainRate->value = $max->getMax();
        $rainRate->units = 'mm/h';
        $rainRate->name = (new \DateTime())->setTimestamp( $max->getMaxtime() )->format("Y-m-d H:i:s");

        return ['max' => $rainRate];
    }

}
