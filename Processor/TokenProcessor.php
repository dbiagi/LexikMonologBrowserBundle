<?php

namespace Lexik\Bundle\MonologBrowserBundle\Processor;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class TokenProcessor
 *
 * @package Lexik\Bundle\MonologBrowserBundle\Processor
 */
class TokenProcessor {

    /** @var TokenStorage */
    private $tokenStorage;

    /** @var SerializerInterface */
    private $serializer;

    function __construct(TokenStorage $tokenStorage, $serializer) {
        $this->tokenStorage = $tokenStorage;
        $this->serializer = $serializer;
    }

    function __invoke(array $record) {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return $record;
        }

        $user = $token->getUser();

        $record['username'] = $token->getUsername();
        $record['user_data'] = $this->serializer->serialize($user, 'json');

        return $record;
    }
}