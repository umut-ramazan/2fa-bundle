<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\TwoFactor\Handler;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\IpWhitelist\IpWhitelistProviderInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @final
 */
class IpWhitelistHandler implements AuthenticationHandlerInterface
{
    public function __construct(private AuthenticationHandlerInterface $authenticationHandler, private IpWhitelistProviderInterface $ipWhitelistProvider)
    {
    }

    public function beginTwoFactorAuthentication(AuthenticationContextInterface $context): TokenInterface
    {
        $request = $context->getRequest();

        // Skip two-factor authentication for whitelisted IPs
        $requestIp = $request->getClientIp();
        if (null !== $requestIp && IpUtils::checkIp($requestIp, $this->ipWhitelistProvider->getWhitelistedIps($context))) {
            return $context->getToken();
        }

        return $this->authenticationHandler->beginTwoFactorAuthentication($context);
    }
}
