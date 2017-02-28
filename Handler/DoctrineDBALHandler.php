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
     * @param Connection   $connection
     * @param TokenStorage $tokenStorage
     * @param string       $tableName
     * @param int          $level
     * @param boolean      $bubble
     */
    public function __construct(Connection $connection, TokenStorage $tokenStorage, $tableName, $level = Logger::DEBUG, $bubble = true) {
        $this->connection = $connection;
        $this->tableName = $tableName;

        parent::__construct($level, $bubble);

        $this->pushProcessor(new WebProcessor());
        $this->pushProcessor(new WebExtendedProcessor());
        $this->pushProcessor(new TokenProcessor($tokenStorage));
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
