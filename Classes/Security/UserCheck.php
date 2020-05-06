<?php

namespace Bitmotion\SecureDownloads\Security;

class UserCheck extends AbstractCheck
{
    protected $user;

    public function hasAccess(): bool
    {
        if ($this->extensionConfiguration->isEnableGroupCheck() || $this->download->getUser() === 0) {
            return true;
        }

        return $this->user === $this->userAspect->get('id');
    }
}
