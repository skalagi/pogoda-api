<?php

namespace SyntaxError\ApiBundle\Service\Archive;

use Doctrine\ORM\EntityManager;
use SyntaxError\ApiBundle\Entity\ArchiveDayOuttemp;
use SyntaxError\ApiBundle\Entity\ArchiveDayWindgustdir;
use SyntaxError\ApiBundle\Interfaces\ArchiveService;
use SyntaxError\ApiBundle\Record\RecordGenerator;
use SyntaxError\ApiBundle\Repository\AbstractDayRepository;

class YearService implements ArchiveService
{
    private $em;

    private $generator;

    public function __construct(EntityManager $entityManager)
    {
        $this->generator = new RecordGenerator();
        $this->em = $entityManager;
    }

    public function highFormatter(\DateTime $dateTime, $archiveName)
    {
        $to = clone $dateTime;
        $from = $dateTime->modify('-1 year');

        $repository = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDay$archiveName");
        if($repository instanceof AbstractDayRepository) {
            $records = $repository->findBetween($from, $to);
            return $this->generator->highGenerate($records, $archiveName);
        }
        return null;
    }

    public function createTemperature(\DateTime $dateTime)
    {
        $max = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayOuttemp")->findYearRecord($dateTime);
        $min = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayOuttemp")->findYearRecord($dateTime, false);

        return $this->generator->generateTemperature($max, $min);
    }

    public function createHumidity(\DateTime $dateTime)
    {
        $max = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayOuthumidity")->findYearRecord($dateTime);
        $min = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayOuthumidity")->findYearRecord($dateTime, false);

        return $this->generator->generateHumidity($max, $min);
    }

    public function createBarometer(\DateTime $dateTime)
    {
        $max = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayBarometer")->findYearRecord($dateTime);
        $min = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayBarometer")->findYearRecord($dateTime, false);

        return $this->generator->generateBarometer($max, $min);
    }

    public function createWindSpeed(\DateTime $dateTime)
    {
        $max = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayWindgust")->findYearRecord($dateTime);

        return $this->generator->generateWindSpeed($max);
    }

    public function createWindDir(\DateTime $dateTime)
    {
        $avg = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayWindgustdir")->avgYear($dateTime);

        return $this->generator->generateWindDir($avg);
    }

    public function createRain(\DateTime $dateTime)
    {
        $sum = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayRain")->findYearSum($dateTime);

        return $this->generator->generateRain($sum);
    }

    public function createRainRate(\DateTime $dateTime)
    {
        $max = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayRainrate")->findYearRecord($dateTime);

        return $this->generator->generateRainRate($max);
    }

}
