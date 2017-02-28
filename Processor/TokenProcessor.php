<?php

namespace Lexik\Bundle\MonologBrowserBundle\Processor;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class TokenProcessor {

    /** @var TokenStorage */
    private $tokenStorage;

    function __construct(TokenStorage $tokenStorage) {
        $this->tokenStorage = $tokenStorage;
    }

    function __invoke(array $record) {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return $record;
        }

        $record['context']['user'] = $token->getUser();

        return $record;
    }
}