<?php

namespace Lexik\Bundle\MonologBrowserBundle\Model;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
class LogRepository {

    /**
     * @var Connection $conn
     */
    protected $conn;

    /**
     * @var string $tableName
     */
    private $tableName;

    /**
     * @var OptionsResolver
     */
    private $optionResolver;

    /**
     * @param Connection $conn
     * @param string     $tableName
     */
    public function __construct(Connection $conn, $tableName) {
        $this->conn = $conn;
        $this->tableName = $tableName;
        $this->configureOptionResolver();
    }

    /**
     * Initialize a QueryBuilder of latest log entries.
     *
     * @param array $criteria
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getLogsQueryBuilder($criteria = []) {
        $qb = $this->createQueryBuilder()
            ->select(
                'l.channel, 
                l.level, 
                l.level_name, 
                l.message, 
                MAX(l.id) AS id, 
                MAX(l.datetime) AS datetime, 
                COUNT(l.id) AS count'
            )
            ->from($this->tableName, 'l')
            ->groupBy('l.channel, l.level, l.level_name, l.message')
            ->orderBy('datetime', 'DESC');

        $criteria = $this->optionResolver->resolve($criteria);

        $this->filter($criteria, $qb);

        return $qb;
    }

    /**
     * Retrieve a log entry by his ID.
     *
     * @param integer $id
     *
     * @return Log|null
     */
    public function getLogById($id) {
        $log = $this->createQueryBuilder()
            ->select('l.*')
            ->from($this->tableName, 'l')
            ->where('l.id = :id')
            ->setParameter(':id', $id)
            ->execute()
            ->fetch();

        if (false !== $log) {
            return new Log($log);
        }
    }

    /**
     * Retrieve last log entry.
     *
     * @return Log|null
     */
    public function getLastLog() {
        $log = $this->createQueryBuilder()
            ->select('l.*')
            ->from($this->tableName, 'l')
            ->orderBy('l.id', 'DESC')
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        if (false !== $log) {
            return new Log($log);
        }
    }

    /**
     * Retrieve similar logs of the given one.
     *
     * @param Log $log
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getSimilarLogsQueryBuilder(Log $log) {
        return $this->createQueryBuilder()
            ->select('l.id, l.channel, l.level, l.level_name, l.message, l.datetime')
            ->from($this->tableName, 'l')
            ->andWhere('l.message = :message')
            ->andWhere('l.channel = :channel')
            ->andWhere('l.level = :level')
            ->andWhere('l.id != :id')
            ->setParameter(':message', $log->getMessage())
            ->setParameter(':channel', $log->getChannel())
            ->setParameter(':level', $log->getLevel())
            ->setParameter(':id', $log->getId());
    }

    /**
     * Returns a array of levels with count entries used by logs.
     *
     * @return array
     */
    public function getLogsLevel() {
        $levels = $this->createQueryBuilder()
            ->select('l.level, l.level_name, COUNT(l.id) AS count')
            ->from($this->tableName, 'l')
            ->groupBy('l.level, l.level_name')
            ->orderBy('l.level', 'DESC')
            ->execute()
            ->fetchAll();

        $normalizedLevels = [];
        foreach ($levels as $level) {
            $desc = sprintf('%s (%s)', $level['level_name'], $level['count']);
            $normalizedLevels[$desc] = $level['level'];
        }

        return $normalizedLevels;
    }

    /**
     * @param              $criteria
     * @param QueryBuilder $qb
     */
    protected function filter($criteria, QueryBuilder $qb) {
        if (null !== $criteria['term']) {
            $qb->andWhere('l.message LIKE :message')
                ->setParameter('message', '%'.str_replace(' ', '%', $criteria['term']).'%')
                ->orWhere('l.channel LIKE :channel')
                ->setParameter('channel', $criteria['term'].'%');
        }

        if (null !== $criteria['level']) {
            $qb->andWhere('l.level = :level')
                ->setParameter('level', $criteria['level']);
        }

        if ($criteria['date_from'] instanceof \DateTime) {
            if ($criteria['time_from']) {
                $criteria['date_from']->setTime(date('G', $criteria['time_from']), date('i', $criteria['time_from']));
            }

            $qb->andWhere('l.datetime >= :date_from')
                ->setParameter('date_from', $this->convertDateToDatabaseValue($criteria['date_from'], $qb));
        }

        if ($criteria['date_to'] instanceof \DateTime) {
            if ($criteria['time_to']) {
                $criteria['date_to']->setTime(date('G', $criteria['time_to']), date('i', $criteria['time_to']));
            }
            $qb->andWhere('l.datetime <= :date_to')
                ->setParameter('date_to', $this->convertDateToDatabaseValue($criteria['date_to'], $qb));
        }

        if (null !== $criteria['order_by']) {
            $qb->orderBy($criteria['order_by'], $criteria['direction']);
        }
    }

    protected function convertDateToDatabaseValue(\DateTime $date, QueryBuilder $qb) {
        return Type::getType(Type::DATETIME)->convertToDatabaseValue(
            $date,
            $qb->getConnection()->getDatabasePlatform()
        );
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function createQueryBuilder() {
        return $this->conn->createQueryBuilder();
    }

    protected function configureOptionResolver() {
        $this->optionResolver = new OptionsResolver();

        $this->optionResolver
            ->setDefaults([
                'term'      => null,
                'date_from' => null,
                'time_from' => null,
                'date_to'   => null,
                'time_to'   => null,
                'level'     => null,
                'order_by'  => null,
                'direction' => 'ASC',
            ])
            ->setAllowedTypes('date_to', [\DateTime::class, 'null'])
            ->setAllowedTypes('date_from', [\DateTime::class, 'null']);
    }
}
