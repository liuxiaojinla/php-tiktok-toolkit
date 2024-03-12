<?php

namespace Xin\TiktokToolkit\Contracts;

interface OAuth
{
    /**
     * @param string $authorizationCode
     * @param string $redirectUri
     * @param array $optional
     * @return array
     */
    public function getAuthorizerToken(string $authorizationCode, string $redirectUri, array $optional = []);

    /**
     * @param string $authorizerRefreshToken
     * @param array $optional
     * @return array
     */
    public function refreshAuthorizerToken(string $authorizerRefreshToken, array $optional = []);

    /**
     * @param array $scope
     * @param string $callbackUrl
     * @param array $optional
     * @return string
     */
    public function createPreAuthorizationUrl(array $scope, string $callbackUrl, array $optional = []);
}
