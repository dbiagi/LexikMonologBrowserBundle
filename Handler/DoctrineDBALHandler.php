<?php

namespace Lexik\Bundle\MonologBrowserBundle\Handler;

use Doctrine\DBAL\Connection;
use Lexik\Bundle\MonologBrowserBundle\Formatter\NormalizerFormatter;
use Lexik\Bundle\MonologBrowserBundle\Processor\TokenProcessor;
use Lexik\Bundle\MonologBrowserBundle\Processor\WebExtendedProcessor;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Serializer\Serializer;

/**
 * Handler to send messages to a database through Doctrine DBAL.
 *
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
class DoctrineDBALHandler extends AbstractProcessingHandler {
    /**
     * @var Connection $connection
     */
    private $connection;

    /**
     * @var string $tableName
     */
    private $tableName;

    /**
     * @param TokenStorage $tokenStorage
     * @param Serializer   $serializer
     * @param bool|int     $level
     * @param boolean      $bubble
     */
    public function __construct(TokenStorage $tokenStorage, Serializer $serializer, $level = Logger::DEBUG, $bubble = true) {
        parent::__construct($level, $bubble);

        $this->pushProcessor(new WebProcessor());
        $this->pushProcessor(new WebExtendedProcessor());
        $this->pushProcessor(new TokenProcessor($tokenStorage, $serializer));
    }

    /**
     * @param Connection $connection
     */
    public function setConnection(Connection $connection) {
        $this->connection = $connection;
    }

    /**
     * @param string $tableName
     */
    public function setTableName($tableName){
        $this->tableName = $tableName;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record) {
        $record = $record['formatted'];

        try {
            $this->connection->insert($this->tableName, $record);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormatter() {
        return new NormalizerFormatter();
    }
}
