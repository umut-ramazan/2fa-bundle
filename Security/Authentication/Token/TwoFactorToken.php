<?php

declare(strict_types=1);

namespace Scheb\TwoFactorBundle\Security\Authentication\Token;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Exception\UnknownTwoFactorProviderException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TwoFactorToken implements TwoFactorTokenInterface
{
    /**
     * @var TokenInterface
     */
    private $authenticatedToken;

    /**
     * @var string|null
     */
    private $credentials;

    /**
     * @var string
     */
    private $providerKey;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var string[]
     */
    private $twoFactorProviders;

    public function __construct(TokenInterface $authenticatedToken, ?string $credentials, string $providerKey, array $twoFactorProviders)
    {
        $this->authenticatedToken = $authenticatedToken;
        $this->credentials = $credentials;
        $this->providerKey = $providerKey;
        $this->twoFactorProviders = $twoFactorProviders;
    }

    public function getUser()
    {
        return $this->authenticatedToken->getUser();
    }

    /**
     * @param string|\Stringable|UserInterface $user
     */
    public function setUser($user): void
    {
        $this->authenticatedToken->setUser($user);
    }

    public function getUsername(): string
    {
        return $this->authenticatedToken->getUsername();
    }

    // Compatibility for Symfony < 5.0
    public function getRoles(): array
    {
        return [];
    }

    // Compatibility for Symfony >= 4.3
    public function getRoleNames(): array
    {
        return $this->getRoles();
    }

    public function getCredentials(): ?string
    {
        return $this->credentials;
    }

    public function eraseCredentials(): void
    {
        $this->credentials = null;
    }

    public function getAuthenticatedToken(): TokenInterface
    {
        return $this->authenticatedToken;
    }

    public function getTwoFactorProviders(): array
    {
        return $this->twoFactorProviders;
    }

    public function preferTwoFactorProvider(string $preferredProvider): void
    {
        $this->removeTwoFactorProvider($preferredProvider);
        array_unshift($this->twoFactorProviders, $preferredProvider);
    }

    public function getCurrentTwoFactorProvider(): ?string
    {
        $first = reset($this->twoFactorProviders);

        return false !== $first ? $first : null;
    }

    public function setTwoFactorProviderComplete(string $providerName): void
    {
        $this->removeTwoFactorProvider($providerName);
    }

    private function removeTwoFactorProvider(string $providerName): void
    {
        $key = array_search($providerName, $this->twoFactorProviders, true);
        if (false === $key) {
            throw new UnknownTwoFactorProviderException(sprintf('Two-factor provider "%s" is not active.', $providerName));
        }
        unset($this->twoFactorProviders[$key]);
    }

    public function allTwoFactorProvidersAuthenticated(): bool
    {
        return 0 === \count($this->twoFactorProviders);
    }

    public function getProviderKey(): string
    {
        return $this->providerKey;
    }

    public function isAuthenticated(): bool
    {
        return true;
    }

    /**
     * @param bool $isAuthenticated
     */
    public function setAuthenticated($isAuthenticated): void
    {
        throw new \RuntimeException('Cannot change authenticated once initialized.');
    }

    public function __serialize(): array
    {
        return [$this->authenticatedToken, $this->credentials, $this->providerKey, $this->attributes, $this->twoFactorProviders];
    }

    // Compatibility for Symfony 4.4 & PHP < 7.4
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function __unserialize(array $data): void
    {
        [$this->authenticatedToken, $this->credentials, $this->providerKey, $this->attributes, $this->twoFactorProviders] = $data;
    }

    // Compatibility for Symfony 4.4 & PHP < 7.4
    public function unserialize($serialized): void
    {
        $this->__unserialize(unserialize($serialized));
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @param string $name
     */
    public function hasAttribute($name): bool
    {
        return \array_key_exists($name, $this->attributes);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getAttribute($name)
    {
        if (!\array_key_exists($name, $this->attributes)) {
            throw new \InvalidArgumentException(sprintf('This token has no "%s" attribute.', $name));
        }

        return $this->attributes[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setAttribute($name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __toString(): string
    {
        return $this->getUsername();
    }
}
